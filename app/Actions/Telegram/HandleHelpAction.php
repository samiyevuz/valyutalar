<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\TelegramService;

class HandleHelpAction
{
    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $message = "📚 <b>" . __('bot.help.title') . "</b>\n\n";

        $message .= "<b>" . __('bot.help.commands_title') . "</b>\n";
        $message .= "/start - " . __('bot.help.cmd_start') . "\n";
        $message .= "/rate - " . __('bot.help.cmd_rate') . "\n";
        $message .= "/convert - " . __('bot.help.cmd_convert') . "\n";
        $message .= "/banks - " . __('bot.help.cmd_banks') . "\n";
        $message .= "/history - " . __('bot.help.cmd_history') . "\n";
        $message .= "/alerts - " . __('bot.help.cmd_alerts') . "\n";
        $message .= "/profile - " . __('bot.help.cmd_profile') . "\n";
        $message .= "/help - " . __('bot.help.cmd_help') . "\n\n";

        $message .= "<b>" . __('bot.help.conversion_title') . "</b>\n";
        $message .= __('bot.help.conversion_examples') . "\n";
        $message .= "• <code>100 USD -> UZS</code>\n";
        $message .= "• <code>50000 UZS to USD</code>\n";
        $message .= "• <code>100 EUR в рубли</code>\n";
        $message .= "• <code>1000 долларов</code>\n\n";

        $message .= "<b>" . __('bot.help.alerts_title') . "</b>\n";
        $message .= __('bot.help.alerts_examples') . "\n";
        $message .= "• <code>USD > 12500</code>\n";
        $message .= "• <code>EUR < 14000</code>\n\n";

        $message .= "<i>" . __('bot.help.support') . "</i>";

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            MainMenuKeyboard::build($user->language)
        );
    }
}

