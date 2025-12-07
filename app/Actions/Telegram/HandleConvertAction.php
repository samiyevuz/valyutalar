<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\CurrencyKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\ConversionHistory;
use App\Models\TelegramUser;
use App\Services\ConversionParser;
use App\Services\CurrencyService;
use App\Services\TelegramService;

class HandleConvertAction
{
    public function __construct(
        private CurrencyService $currencyService,
        private ConversionParser $conversionParser,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $args = $update->getCommandArgs();

        if ($args) {
            $conversion = $this->conversionParser->parse($args);

            if ($conversion) {
                $this->performConversion($update->getChatId(), $conversion, $user, $telegram);
                return;
            }
        }

        // Show conversion instructions
        $this->showConversionInstructions($update->getChatId(), $user, $telegram);
    }

    public function performConversion(
        int $chatId,
        array $conversion,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        try {
            // Validate conversion data
            if (empty($conversion['amount']) || empty($conversion['from']) || empty($conversion['to'])) {
                \Log::warning('Invalid conversion data', ['conversion' => $conversion]);
                $telegram->sendMessage(
                    $chatId,
                    '❌ ' . __('bot.errors.conversion_failed', locale: $user->language),
                    MainMenuKeyboard::build($user->language)
                );
                return;
            }

            $result = $this->currencyService->convertAmount(
                $conversion['amount'],
                $conversion['from'],
                $conversion['to']
            );

            if (!$result || $result->rate <= 0) {
                \Log::warning('Conversion failed', [
                    'conversion' => $conversion,
                    'result' => $result,
                ]);
                $telegram->sendMessage(
                    $chatId,
                    '❌ ' . __('bot.errors.conversion_failed', locale: $user->language),
                    MainMenuKeyboard::build($user->language)
                );
                return;
            }

            // Save to history
            try {
                ConversionHistory::create([
                    'telegram_user_id' => $user->id,
                    'currency_from' => $conversion['from'],
                    'currency_to' => $conversion['to'],
                    'amount_from' => $conversion['amount'],
                    'amount_to' => $result->amountTo,
                    'rate_used' => $result->rate,
                ]);
            } catch (\Exception $historyError) {
                \Log::warning('Failed to save conversion history', [
                    'error' => $historyError->getMessage(),
                ]);
                // Continue anyway, not critical
            }

            $message = $this->formatConversionResult($result, $user->language);

            if (empty($message)) {
                $message = '❌ ' . __('bot.errors.conversion_failed', locale: $user->language);
            }

            $telegram->sendMessage($chatId, $message, MainMenuKeyboard::buildCompact($user->language));
        } catch (\Exception $e) {
            \Log::error('Error in performConversion', [
                'conversion' => $conversion ?? [],
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

    private function showConversionInstructions(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $lang = $user->language;
        $message = "🔄 <b>" . __('bot.convert.title', locale: $lang) . "</b>\n\n";
        $message .= __('bot.convert.instructions', locale: $lang) . "\n\n";
        $message .= "<b>" . __('bot.convert.examples', locale: $lang) . ":</b>\n";
        $message .= "• <code>100 USD -> UZS</code>\n";
        $message .= "• <code>50000 UZS to EUR</code>\n";
        $message .= "• <code>1000 RUB -> USD</code>\n";
        $message .= "• <code>500 EUR</code>\n\n";
        $message .= "<i>" . __('bot.convert.hint', locale: $lang) . "</i>";

        // Add main menu button to keyboard
        $keyboard = CurrencyKeyboard::buildForConversion('from', $lang);
        $telegram->sendMessage(
            $chatId,
            $message,
            $keyboard
        );
    }

    private function formatConversionResult($result, string $lang): string
    {
        $lines = [
            "💱 <b>" . __('bot.convert.result_title', locale: $lang) . "</b>",
            '',
            $result->formatDetailed(),
            '',
            '<i>' . now('Asia/Tashkent')->format('d.m.Y H:i') . '</i>',
        ];

        return implode("\n", $lines);
    }
}

