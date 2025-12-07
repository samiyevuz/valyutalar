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
        // Use error_log first (most reliable, works even if file system fails)
        @error_log('[WEBHOOK] === WEBHOOK START === ' . date('Y-m-d H:i:s') . ' | IP: ' . $request->ip() . ' | Method: ' . $request->method() . ' | URL: ' . $request->fullUrl());
        
        $this->forceLog('=== WEBHOOK START ===', [
            'time' => date('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
        ]);

        try {
            $rawBody = $request->getContent();
            $this->forceLog('Raw request body', ['body_length' => strlen($rawBody), 'body_preview' => substr($rawBody, 0, 200)]);
            
            $data = $request->all();
            $this->forceLog('Request data received', [
                'has_data' => !empty($data),
                'data_keys' => array_keys($data),
                'update_id' => $data['update_id'] ?? 'none',
            ]);
            
            error_log('[WEBHOOK] Data: ' . json_encode(['has_data' => !empty($data), 'update_id' => $data['update_id'] ?? 'none']));

            if (empty($data)) {
                $this->forceLog('Empty data - returning');
                return response()->json(['ok' => true]);
            }

            $this->forceLog('Creating DTO', ['update_id' => $data['update_id'] ?? 'none']);
            
            try {
                $update = TelegramUpdateDTO::fromArray($data);
                $this->forceLog('DTO created successfully');
            } catch (\Exception $e) {
                $this->forceLog('ERROR creating DTO', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                return response()->json(['ok' => true]);
            }

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
                    'trace' => substr($e->getTraceAsString(), 0, 500),
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
                'has_text' => $update->hasText(),
                'text_preview' => $update->hasText() ? substr($update->getText(), 0, 50) : null,
            ]);

            try {
                $this->processUpdate($update, $user);
                $this->forceLog('=== WEBHOOK SUCCESS ===');
                error_log('[WEBHOOK] Success');
            } catch (\Exception $processError) {
                $this->forceLog('ERROR in processUpdate', [
                    'error' => $processError->getMessage(),
                    'file' => $processError->getFile(),
                    'line' => $processError->getLine(),
                    'trace' => substr($processError->getTraceAsString(), 0, 1000),
                ]);
                error_log('[WEBHOOK] Process error: ' . $processError->getMessage());
            }

            return response()->json(['ok' => true]);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = substr($e->getTraceAsString(), 0, 2000);
            
            $this->forceLog('=== WEBHOOK EXCEPTION ===', [
                'error' => $errorMsg,
                'file' => $errorFile,
                'line' => $errorLine,
                'trace' => $errorTrace,
                'class' => get_class($e),
            ]);
            
            error_log('[WEBHOOK] EXCEPTION: ' . $errorMsg . ' in ' . $errorFile . ':' . $errorLine);
            error_log('[WEBHOOK] TRACE: ' . substr($errorTrace, 0, 500));

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

        // Create directory if not exists with proper permissions
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
            @chmod($logDir, 0755);
        }

        // Ensure directory exists
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // Create log file if not exists
        if (!file_exists($logFile)) {
            @touch($logFile);
            @chmod($logFile, 0666);
        }

        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = sprintf(
            "[%s] %s%s\n",
            date('Y-m-d H:i:s'),
            $message,
            $contextStr
        );

        // Always log to PHP error_log first (most reliable)
        @error_log('[WEBHOOK-DEBUG] ' . trim($logEntry));

        // Try multiple methods to write
        $written = false;
        try {
            // First try with file_put_contents
            $result = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            $written = $result !== false;
            
            if (!$written) {
                // Try with fopen/fwrite
                $fp = @fopen($logFile, 'a');
                if ($fp) {
                    @flock($fp, LOCK_EX);
                    @fwrite($fp, $logEntry);
                    @flock($fp, LOCK_UN);
                    @fclose($fp);
                    $written = true;
                }
            }
        } catch (\Exception $e) {
            // Already logged to error_log above
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
        // Update user activity
        try {
            $user->updateActivity();
        } catch (\Exception $e) {
            $this->forceLog('ERROR updating user activity', ['error' => $e->getMessage()]);
        }

        try {
            // Handle callback queries FIRST (they have priority)
            if ($update->isCallbackQuery()) {
                $callbackData = $update->getCallbackData();
                $this->forceLog('Processing callback query', [
                    'callback_data' => $callbackData,
                    'callback_id' => $update->getCallbackQueryId(),
                    'chat_id' => $update->getChatId(),
                ]);
                
                try {
                    $callbackAction = app(HandleCallbackAction::class);
                    $callbackAction->execute($update, $user, $this->telegramService);
                    $this->forceLog('Callback query processed successfully');
                } catch (\Exception $e) {
                    $this->forceLog('ERROR in callback handler', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => substr($e->getTraceAsString(), 0, 1000),
                    ]);
                    
                    // Try to send error message to user
                    try {
                        $this->telegramService->sendMessage(
                            $update->getChatId(),
                            '❌ ' . __('bot.errors.api_error', locale: $user->language)
                        );
                    } catch (\Exception $sendError) {
                        $this->forceLog('ERROR sending error message', ['error' => $sendError->getMessage()]);
                    }
                }
                return;
            }

            // Handle commands
            if ($update->isCommand()) {
                $command = $update->getCommand();
                $this->forceLog('Processing command', [
                    'command' => $command,
                    'chat_id' => $update->getChatId(),
                ]);
                $this->handleCommand($update, $user);
                return;
            }

            // Handle text messages
            if ($update->hasText()) {
                $text = $update->getText();
                $this->forceLog('Processing text message', [
                    'text_preview' => substr($text, 0, 100),
                    'text_length' => strlen($text),
                    'chat_id' => $update->getChatId(),
                ]);
                $this->handleTextMessage($update, $user);
                return;
            }

            $this->forceLog('Update not processed - no command, callback, or text');
        } catch (\Exception $e) {
            $this->forceLog('ERROR in processUpdate', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
            throw $e; // Re-throw to be caught by outer try-catch
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
                'available_commands' => array_keys($commands ?? []),
                'chat_id' => $update->getChatId(),
            ]);

            if (empty($commands)) {
                $this->forceLog('ERROR: No commands configured in config/telegram.php');
                return;
            }

            if (isset($commands[$command])) {
                try {
                    $actionClass = $commands[$command];
                    $this->forceLog('Creating action', ['action_class' => $actionClass]);
                    
                    $action = app($actionClass);
                    $this->forceLog('Action created', ['action_class' => get_class($action)]);
                    
                    $action->execute($update, $user, $this->telegramService);
                    $this->forceLog('Command executed successfully', ['command' => $command]);
                } catch (\Exception $actionError) {
                    $this->forceLog('ERROR executing action', [
                        'command' => $command,
                        'action_class' => $commands[$command] ?? 'unknown',
                        'error' => $actionError->getMessage(),
                        'file' => $actionError->getFile(),
                        'line' => $actionError->getLine(),
                        'trace' => substr($actionError->getTraceAsString(), 0, 2000),
                    ]);
                    
                    // Try to send error message to user
                    try {
                        $this->telegramService->sendMessage(
                            $update->getChatId(),
                            '❌ ' . __('bot.errors.api_error', locale: $user->language)
                        );
                    } catch (\Exception $sendError) {
                        $this->forceLog('ERROR sending error message', ['error' => $sendError->getMessage()]);
                    }
                }
            } else {
                $this->forceLog('Command not found', [
                    'command' => $command,
                    'available_commands' => array_keys($commands),
                ]);
                
                // Send help message for unknown commands
                try {
                    $this->telegramService->sendMessage(
                        $update->getChatId(),
                        '❓ ' . __('bot.help.message', locale: $user->language)
                    );
                } catch (\Exception $sendError) {
                    $this->forceLog('ERROR sending help message', ['error' => $sendError->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            $this->forceLog('ERROR in handleCommand', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
        }
    }

    private function handleTextMessage(TelegramUpdateDTO $update, TelegramUser $user): void
    {
        try {
            $text = $update->getText();

            // Check if it's a command (fallback - in case isCommand() didn't catch it)
            if ($update->isCommand()) {
                $this->forceLog('Command detected in handleTextMessage, redirecting to handleCommand');
                $this->handleCommand($update, $user);
                return;
            }

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
                    '✅ ' . __('bot.alerts.created', locale: $user->language) . "\n\n" . $alert->getDescription()
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
                '❌ ' . __('bot.errors.invalid_amount', locale: $user->language)
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
                '❌ ' . __('bot.errors.invalid_amount', locale: $user->language)
            );
            return;
        }

        $user->clearState();

        $alert = $this->alertService->createAlert($user, $currency, 'UZS', $condition, $amount);

        $this->telegramService->sendMessage(
            $chatId,
            '✅ ' . __('bot.alerts.created', locale: $user->language) . "\n\n🔔 " . $alert->getDescription()
        );
    }

    private function parseAmount(string $text): float
    {
        $text = str_replace([' ', ','], ['', '.'], trim($text));
        return max(0, (float) $text);
    }
}
