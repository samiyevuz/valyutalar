<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\CurrencyKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\CurrencyService;
use App\Services\TelegramService;

class HandleRateAction
{
    public function __construct(
        private CurrencyService $currencyService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $args = $update->getCommandArgs();

        if ($args) {
            $currency = strtoupper(trim($args));
            $this->sendCurrencyRate($update->getChatId(), $currency, $user, $telegram);
            return;
        }

        // Show currency selection keyboard with main menu button
        $keyboard = CurrencyKeyboard::build('rate', $user->language);
        $telegram->sendMessage(
            $update->getChatId(),
            '💱 ' . __('bot.rates.select_currency'),
            $keyboard
        );
    }

    public function sendCurrencyRate(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram,
        ?int $messageId = null
    ): void {
        if ($currency === 'ALL' || $currency === 'all') {
            $this->sendAllRates($chatId, $user, $telegram);
            return;
        }

        // Normalize currency code
        $currency = strtoupper(trim($currency));

        $rate = $this->currencyService->getRate($currency);

        if (!$rate) {
            \Log::warning('Currency rate not found', [
                'currency' => $currency,
                'user_id' => $user->id,
            ]);

            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.currency_not_found', ['currency' => $currency], $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        $trend = $this->currencyService->getTrend($currency, 7);
        $message = $this->formatSingleRate($rate, $trend, $user->language);

        if ($messageId) {
            try {
                $telegram->editMessageText($chatId, $messageId, $message, MainMenuKeyboard::buildCompact($user->language));
            } catch (\Exception $e) {
                // If edit fails, delete old message and send new one
                \Log::warning('Failed to edit rate message, deleting and sending new one', ['error' => $e->getMessage()]);
                try {
                    $telegram->deleteMessage($chatId, $messageId);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
                $telegram->sendMessage($chatId, $message, MainMenuKeyboard::buildCompact($user->language));
            }
        } else {
            $telegram->sendMessage($chatId, $message, MainMenuKeyboard::buildCompact($user->language));
        }
    }

    private function sendAllRates(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $currencies = $user->getFavoriteCurrencies();
        $message = $this->currencyService->formatRatesMessage($currencies, $user->language);

        $telegram->sendMessage($chatId, $message, MainMenuKeyboard::buildCompact($user->language));
    }

    private function formatSingleRate($rate, array $trend, string $lang): string
    {
        $emoji = match ($trend['trend']) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️',
        };

        $changeText = '';
        if ($trend['change_percent'] != 0) {
            $changeText = sprintf(' (%+.2f%%)', $trend['change_percent']);
        }

        $lines = [
            "{$emoji} <b>{$rate->currencyCode}/UZS</b>",
            '',
            '💰 ' . __('bot.rates.current_rate', locale: $lang) . ': <b>' . number_format($rate->rate, 2, '.', ' ') . '</b> UZS',
            '',
            "{$emoji} " . __('bot.rates.weekly_change', locale: $lang) . ": <b>{$changeText}</b>",
            '',
            '<i>' . __('bot.rates.updated_at', ['time' => now('Asia/Tashkent')->format('d.m.Y H:i')], $lang) . '</i>',
        ];

        return implode("\n", $lines);
    }
}

