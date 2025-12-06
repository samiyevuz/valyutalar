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
            '🏦 ' . __('bot.banks.select_currency'),
            CurrencyKeyboard::buildForBanks($user->language)
        );
    }

    public function showBankRates(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        // Normalize currency code
        $currency = strtoupper(trim($currency));

        \Log::info('Showing bank rates', [
            'currency' => $currency,
            'chat_id' => $chatId,
        ]);

        // Send typing indicator
        $telegram->sendChatAction($chatId, 'typing');

        // Ensure bank rates are fetched
        try {
            $fetched = $this->bankRatesService->fetchAllBankRates();
            \Log::info('Bank rates fetched', ['count' => $fetched]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch bank rates', [
                'currency' => $currency,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        $message = $this->bankRatesService->formatBankRatesMessage($currency, $user->language);

        \Log::info('Bank rates message formatted', [
            'currency' => $currency,
            'message_length' => strlen($message),
            'message_preview' => substr($message, 0, 100),
        ]);

        try {
            $result = $telegram->sendMessage(
                $chatId,
                $message,
                CurrencyKeyboard::buildForBanks($user->language)
            );
            
            \Log::info('Bank rates message sent', [
                'currency' => $currency,
                'success' => $result['ok'] ?? false,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send bank rates message', [
                'currency' => $currency,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}

