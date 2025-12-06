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

        // Send typing indicator
        $telegram->sendChatAction($chatId, 'typing');

        // Ensure bank rates are fetched
        try {
            $this->bankRatesService->fetchAllBankRates();
        } catch (\Exception $e) {
            \Log::error('Failed to fetch bank rates', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
        }

        $message = $this->bankRatesService->formatBankRatesMessage($currency, $user->language);

        $telegram->sendMessage(
            $chatId,
            $message,
            CurrencyKeyboard::buildForBanks($user->language)
        );
    }
}

