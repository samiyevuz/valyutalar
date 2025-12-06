<?php

namespace App\Http\Controllers;

use App\Actions\Telegram\HandleCallbackAction;
use App\Actions\Telegram\HandleConvertAction;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\AlertService;
use App\Services\ConversionParser;
use App\Services\CurrencyService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramService $telegramService,
        private CurrencyService $currencyService,
        private ConversionParser $conversionParser,
        private AlertService $alertService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // ALWAYS log - even if everything fails
        $this->forceLog('=== WEBHOOK START ===', [
            'time' => date('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        try {
            $data = $request->all();
            $this->forceLog('Request data received', ['has_data' => !empty($data)]);

            if (empty($data)) {
                $this->forceLog('Empty data - returning');
                return response()->json(['ok' => true]);
            }

            $this->forceLog('Creating DTO', ['update_id' => $data['update_id'] ?? 'none']);
            
            $update = TelegramUpdateDTO::fromArray($data);
            $this->forceLog('DTO created successfully');

            // Get or create user
            try {
                $this->forceLog('Getting user', ['user_id' => $update->getUser()->id ?? 'none']);
                $user = TelegramUser::findOrCreateFromDTO($update->getUser());
                $this->forceLog('User found/created', [
                    'telegram_id' => $user->telegram_id,
                    'username' => $user->username,
                    'language' => $user->language,
                ]);
            } catch (\Exception $e) {
                $this->forceLog('ERROR: User creation failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                return response()->json(['ok' => true]);
            }

            if ($user->is_blocked) {
                $this->forceLog('User is blocked');
                return response()->json(['ok' => true]);
            }

            app()->setLocale($user->language ?? 'en');
            $this->forceLog('Locale set', ['locale' => $user->language ?? 'en']);

            // Process update
            $this->forceLog('Processing update', [
                'is_command' => $update->isCommand(),
                'is_callback' => $update->isCallbackQuery(),
                'command' => $update->getCommand(),
            ]);

            $this->processUpdate($update, $user);

            $this->forceLog('=== WEBHOOK SUCCESS ===');
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            $this->forceLog('=== WEBHOOK EXCEPTION ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);

            return response()->json(['ok' => true]);
        }
    }

    /**
     * Force log to file - bypasses all Laravel logging mechanisms
     */
    private function forceLog(string $message, array $context = []): void
    {
        $logFile = storage_path('logs/webhook-debug.log');
        $logDir = dirname($logFile);

        // Create directory if not exists
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = sprintf(
            "[%s] %s%s\n",
            date('Y-m-d H:i:s'),
            $message,
            $contextStr
        );

        // Try multiple methods to write
        try {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Try error_log as fallback
            error_log($logEntry);
        }
        
        // Also try Laravel Log
        try {
            Log::info($message, $context);
        } catch (\Exception $e) {
            // Ignore
        }
    }

    private function processUpdate(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        try {
            // Handle callback queries
            if ($update->isCallbackQuery()) {
                $this->forceLog('Processing callback query');
                app(HandleCallbackAction::class)->execute($update, $user, $this->telegramService);
                return;
            }

            // Handle commands
            if ($update->isCommand()) {
                $this->forceLog('Processing command', ['command' => $update->getCommand()]);
                $this->handleCommand($update, $user);
                return;
            }

            // Handle text messages
            if ($update->hasText()) {
                $this->forceLog('Processing text message', ['text' => substr($update->getText(), 0, 50)]);
                $this->handleTextMessage($update, $user);
            }
        } catch (\Exception $e) {
            $this->forceLog('ERROR in processUpdate', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function handleCommand(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        try {
            $command = $update->getCommand();
            $commands = config('telegram.commands');

            $this->forceLog('Handling command', [
                'command' => $command,
                'has_handler' => isset($commands[$command]),
            ]);

            if (isset($commands[$command])) {
                $action = app($commands[$command]);
                $action->execute($update, $user, $this->telegramService);
                $this->forceLog('Command executed', ['command' => $command]);
            } else {
                $this->forceLog('Command not found', ['command' => $command]);
            }
        } catch (\Exception $e) {
            $this->forceLog('ERROR in handleCommand', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function handleTextMessage(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        try {
            $text = $update->getText();

            // Check for state-based conversation
            if ($user->state) {
                $this->forceLog('Stateful message', ['state' => $user->state]);
                $this->handleStatefulMessage($update, $user);
                return;
            }

            // Check for conversion pattern
            $conversion = $this->conversionParser->parse($text);

            if ($conversion) {
                $this->forceLog('Conversion detected', $conversion);
                app(HandleConvertAction::class)->performConversion(
                    $update->getChatId(),
                    $conversion,
                    $user,
                    $this->telegramService
                );
                return;
            }

            // Check for alert pattern
            $alert = $this->alertService->parseAndCreateAlert($text, $user);

            if ($alert) {
                $this->forceLog('Alert created');
                $this->telegramService->sendMessage(
                    $update->getChatId(),
                    '✅ ' . __('bot.alerts.created') . "\n\n" . $alert->getDescription()
                );
                return;
            }

            $this->forceLog('Text message not processed');
        } catch (\Exception $e) {
            $this->forceLog('ERROR in handleTextMessage', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    private function handleStatefulMessage(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        $text = $update->getText();
        $chatId = $update->getChatId();

        match ($user->state) {
            'convert_awaiting_amount' => $this->handleConvertAmount($chatId, $text, $user),
            'alert_awaiting_amount' => $this->handleAlertAmount($chatId, $text, $user),
            default => $user->clearState(),
        };
    }

    private function handleConvertAmount(int $chatId, string $text, TelegramUser $user): void
    {
        $stateData = $user->state_data ?? [];
        $from = $stateData['from'] ?? 'USD';
        $to = $stateData['to'] ?? 'UZS';

        $amount = $this->parseAmount($text);

        if ($amount <= 0) {
            $this->telegramService->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.invalid_amount')
            );
            return;
        }

        $user->clearState();

        app(HandleConvertAction::class)->performConversion(
            $chatId,
            ['amount' => $amount, 'from' => $from, 'to' => $to],
            $user,
            $this->telegramService
        );
    }

    private function handleAlertAmount(int $chatId, string $text, TelegramUser $user): void
    {
        $stateData = $user->state_data ?? [];
        $currency = $stateData['currency'] ?? 'USD';
        $condition = $stateData['condition'] ?? 'above';

        $amount = $this->parseAmount($text);

        if ($amount <= 0) {
            $this->telegramService->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.invalid_amount')
            );
            return;
        }

        $user->clearState();

        $alert = $this->alertService->createAlert($user, $currency, 'UZS', $condition, $amount);

        $this->telegramService->sendMessage(
            $chatId,
            '✅ ' . __('bot.alerts.created') . "\n\n🔔 " . $alert->getDescription()
        );
    }

    private function parseAmount(string $text): float
    {
        $text = str_replace([' ', ','], ['', '.'], trim($text));
        return max(0, (float) $text);
    }
}
