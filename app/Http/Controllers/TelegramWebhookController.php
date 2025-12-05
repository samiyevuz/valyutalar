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
        try {
            $data = $request->all();

            if (empty($data)) {
                return response()->json(['ok' => true]);
            }

            $update = TelegramUpdateDTO::fromArray($data);

            // Get or create user
            $user = TelegramUser::findOrCreateFromDTO($update->getUser());

            if ($user->is_blocked) {
                return response()->json(['ok' => true]);
            }

            // Set locale
            app()->setLocale($user->language ?? 'en');

            // Process update
            $this->processUpdate($update, $user);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);

            return response()->json(['ok' => true]);
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

