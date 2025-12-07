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
        $result = $this->currencyService->convertAmount(
            $conversion['amount'],
            $conversion['from'],
            $conversion['to']
        );

        if ($result->rate <= 0) {
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.conversion_failed'),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        // Save to history
        ConversionHistory::create([
            'telegram_user_id' => $user->id,
            'currency_from' => $conversion['from'],
            'currency_to' => $conversion['to'],
            'amount_from' => $conversion['amount'],
            'amount_to' => $result->amountTo,
            'rate_used' => $result->rate,
        ]);

        $message = $this->formatConversionResult($result, $user->language);

        $telegram->sendMessage($chatId, $message, MainMenuKeyboard::buildCompact($user->language));
    }

    private function showConversionInstructions(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $message = "🔄 <b>" . __('bot.convert.title') . "</b>\n\n";
        $message .= __('bot.convert.instructions') . "\n\n";
        $message .= "<b>" . __('bot.convert.examples') . ":</b>\n";
        $message .= "• <code>100 USD -> UZS</code>\n";
        $message .= "• <code>50000 UZS to EUR</code>\n";
        $message .= "• <code>1000 рублей в доллары</code>\n";
        $message .= "• <code>500 евро</code>\n\n";
        $message .= "<i>" . __('bot.convert.hint') . "</i>";

        $telegram->sendMessage(
            $chatId,
            $message,
            CurrencyKeyboard::buildForConversion('from', $user->language)
        );
    }

    private function formatConversionResult($result, string $lang): string
    {
        $lines = [
            "💱 <b>" . __('bot.convert.result_title', locale: $lang) . "</b>",
            '',
            $result->formatDetailed(),
            '',
            '<i>' . now('Asia/Tashkent')->format('H:i d.m.Y') . '</i>',
        ];

        return implode("\n", $lines);
    }
}

