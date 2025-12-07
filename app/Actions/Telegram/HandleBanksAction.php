<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\CurrencyKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\BankRatesService;
use App\Services\TelegramService;

class HandleBanksAction
{
    public function __construct(
        private BankRatesService $bankRatesService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $args = $update->getCommandArgs();

        if ($args) {
            $currency = strtoupper(trim($args));
            $this->showBankRates($update->getChatId(), $currency, $user, $telegram);
            return;
        }

        // Show currency selection
        $telegram->sendMessage(
            $update->getChatId(),
            '🏦 ' . __('bot.banks.select_currency', locale: $user->language),
            CurrencyKeyboard::buildForBanks($user->language)
        );
    }

    public function showBankRates(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram,
        ?int $messageId = null
    ): void {
        try {
            // Normalize currency code
            $currency = strtoupper(trim($currency));

            if (empty($currency)) {
                \Log::warning('Empty currency code provided for bank rates', [
                    'chat_id' => $chatId,
                ]);
                $telegram->sendMessage(
                    $chatId,
                    '❌ ' . __('bot.errors.currency_not_found', ['currency' => ''], $user->language),
                    MainMenuKeyboard::build($user->language)
                );
                return;
            }

            \Log::info('Showing bank rates', [
                'currency' => $currency,
                'chat_id' => $chatId,
            ]);

            // Send typing indicator
            try {
                $telegram->sendChatAction($chatId, 'typing');
            } catch (\Exception $actionError) {
                // Ignore chat action errors
            }

            // Always fetch fresh bank rates for real-time data
            try {
                $fetched = $this->bankRatesService->fetchAllBankRates();
                \Log::info('Bank rates fetched', [
                    'count' => $fetched,
                    'currency' => $currency,
                    'timestamp' => now('Asia/Tashkent')->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to fetch bank rates', [
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                // Continue anyway, might have cached data
            }

            $message = $this->bankRatesService->formatBankRatesMessage($currency, $user->language);

            if (empty($message)) {
                $message = '❌ ' . __('bot.banks.no_data', locale: $user->language);
            }

            \Log::info('Bank rates message formatted', [
                'currency' => $currency,
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 100),
            ]);

            if ($messageId) {
                try {
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildForBanks($user->language);
                    $result = $telegram->editMessageText(
                        $chatId,
                        $messageId,
                        $message,
                        $keyboard
                    );
                } catch (\Exception $e) {
                    // If edit fails, delete old message and send new one
                    \Log::warning('Failed to edit bank rates message, deleting and sending new one', ['error' => $e->getMessage()]);
                    try {
                        $telegram->deleteMessage($chatId, $messageId);
                    } catch (\Exception $deleteError) {
                        // Ignore delete errors
                    }
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildForBanks($user->language);
                    $result = $telegram->sendMessage(
                        $chatId,
                        $message,
                        $keyboard
                    );
                }
            } else {
                // Add main menu button to keyboard
                $keyboard = CurrencyKeyboard::buildForBanks($user->language);
                $result = $telegram->sendMessage(
                    $chatId,
                    $message,
                    $keyboard
                );
            }
            
            \Log::info('Bank rates message sent', [
                'currency' => $currency,
                'success' => $result['ok'] ?? false,
                'edited' => $messageId !== null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send bank rates message', [
                'currency' => $currency ?? 'unknown',
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            try {
                $telegram->sendMessage(
                    $chatId,
                    '❌ ' . __('bot.errors.api_error', locale: $user->language),
                    MainMenuKeyboard::build($user->language)
                );
            } catch (\Exception $sendError) {
                \Log::error('Failed to send error message', ['error' => $sendError->getMessage()]);
            }
        }
    }
}

