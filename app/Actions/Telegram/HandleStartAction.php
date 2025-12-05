<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\LanguageKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\TelegramService;

class HandleStartAction
{
    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        // If user is new or language not set, show language selection
        if ($user->wasRecentlyCreated || !$user->language) {
            $this->showLanguageSelection($update, $telegram);
            return;
        }

        // Show welcome message with main menu
        $this->showWelcomeMessage($update, $user, $telegram);
    }

    private function showLanguageSelection(TelegramUpdateDTO $update, TelegramService $telegram): void
    {
        $message = "🌍 <b>Welcome! / Добро пожаловать! / Xush kelibsiz!</b>\n\n";
        $message .= "Please select your language:\n";
        $message .= "Пожалуйста, выберите язык:\n";
        $message .= "Iltimos, tilni tanlang:";

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            LanguageKeyboard::build()
        );
    }

    private function showWelcomeMessage(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $name = $user->getDisplayName();

        $message = __('bot.welcome.greeting', ['name' => $name]) . "\n\n";
        $message .= __('bot.welcome.description') . "\n\n";
        $message .= __('bot.welcome.features') . "\n";
        $message .= "• " . __('bot.welcome.feature_rates') . "\n";
        $message .= "• " . __('bot.welcome.feature_convert') . "\n";
        $message .= "• " . __('bot.welcome.feature_banks') . "\n";
        $message .= "• " . __('bot.welcome.feature_alerts') . "\n";
        $message .= "• " . __('bot.welcome.feature_history') . "\n\n";
        $message .= __('bot.welcome.select_action');

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            MainMenuKeyboard::build($user->language)
        );
    }
}

