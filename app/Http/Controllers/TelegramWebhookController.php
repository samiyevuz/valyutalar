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
use Illuminate\Support\Facades\File;

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
        // Force log to file directly (for debugging)
        $this->writeLogDirectly('=== WEBHOOK RECEIVED ===', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_data' => !empty($request->all()),
            'data_keys' => array_keys($request->all()),
            'time' => now()->toDateTimeString(),
        ]);

        // Log incoming request
        Log::info('Telegram webhook received', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'has_data' => !empty($request->all()),
            'data_keys' => array_keys($request->all()),
        ]);

        try {
            $data = $request->all();

            if (empty($data)) {
                $this->writeLogDirectly('Empty webhook data');
                Log::warning('Empty webhook data received');
                return response()->json(['ok' => true]);
            }

            $this->writeLogDirectly('Processing update', [
                'update_id' => $data['update_id'] ?? null,
                'has_message' => isset($data['message']),
                'has_callback' => isset($data['callback_query']),
            ]);

            Log::debug('Processing Telegram update', [
                'update_id' => $data['update_id'] ?? null,
                'has_message' => isset($data['message']),
                'has_callback' => isset($data['callback_query']),
            ]);

            $update = TelegramUpdateDTO::fromArray($data);

            // Get or create user (with database error handling)
            try {
                $user = TelegramUser::findOrCreateFromDTO($update->getUser());
                
                $this->writeLogDirectly('User processed', [
                    'telegram_id' => $user->telegram_id,
                    'username' => $user->username,
                    'language' => $user->language,
                ]);

                Log::info('User processed', [
                    'telegram_id' => $user->telegram_id,
                    'username' => $user->username,
                    'language' => $user->language,
                ]);
            } catch (\Exception $e) {
                $this->writeLogDirectly('ERROR: Failed to get/create user', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => $update->getUser()->id ?? null,
                ]);

                Log::error('Failed to get/create user', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => $update->getUser()->id ?? null,
                ]);
                // Return OK to prevent Telegram retries
                return response()->json(['ok' => true]);
            }

            if ($user->is_blocked) {
                $this->writeLogDirectly('User is blocked', ['telegram_id' => $user->telegram_id]);
                return response()->json(['ok' => true]);
            }

            // Set locale
            app()->setLocale($user->language ?? 'en');

            // Process update
            $this->writeLogDirectly('Processing update', [
                'is_command' => $update->isCommand(),
                'is_callback' => $update->isCallbackQuery(),
                'command' => $update->getCommand(),
            ]);

            Log::info('Processing update', [
                'is_command' => $update->isCommand(),
                'is_callback' => $update->isCallbackQuery(),
                'command' => $update->getCommand(),
            ]);

            $this->processUpdate($update, $user);

            $this->writeLogDirectly('Update processed successfully');
            Log::info('Update processed successfully');

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            $this->writeLogDirectly('EXCEPTION', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            // Always return OK to prevent Telegram retries
            return response()->json(['ok' => true]);
        }
    }

    /**
     * Write log directly to file (bypass Laravel logging system)
     */
    private function writeLogDirectly(string $message, array $context = []): void
    {
        try {
            $logPath = storage_path('logs/telegram-webhook.log');
            $logDir = dirname($logPath);

            // Ensure directory exists
            if (!File::exists($logDir)) {
                File::makeDirectory($logDir, 0755, true);
            }

            $logEntry = sprintf(
                "[%s] %s %s\n",
                now()->toDateTimeString(),
                $message,
                !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ''
            );

            File::append($logPath, $logEntry);
        } catch (\Exception $e) {
            // Silently fail - don't break webhook processing
            error_log('Failed to write log: ' . $e->getMessage());
        }
    }

    private function processUpdate(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        // Handle callback queries
        if ($update->isCallbackQuery()) {
            app(HandleCallbackAction::class)->execute($update, $user, $this->telegramService);
            return;
        }

        // Handle commands
        if ($update->isCommand()) {
            $this->handleCommand($update, $user);
            return;
        }

        // Handle text messages
        if ($update->hasText()) {
            $this->handleTextMessage($update, $user);
        }
    }

    private function handleCommand(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        $command = $update->getCommand();
        $commands = config('telegram.commands');

        if (isset($commands[$command])) {
            $action = app($commands[$command]);
            $action->execute($update, $user, $this->telegramService);
        }
    }

    private function handleTextMessage(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        $text = $update->getText();

        // Check for state-based conversation
        if ($user->state) {
            $this->handleStatefulMessage($update, $user);
            return;
        }

        // Check for conversion pattern
        $conversion = $this->conversionParser->parse($text);

        if ($conversion) {
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
            $this->telegramService->sendMessage(
                $update->getChatId(),
                '✅ ' . __('bot.alerts.created') . "\n\n" . $alert->getDescription()
            );
            return;
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
