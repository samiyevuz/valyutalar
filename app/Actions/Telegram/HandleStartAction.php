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

        // Always show welcome message when /start is called
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

        $message = __('bot.welcome', ['name' => $name]) . "\n\n";
        $message .= "💱 " . __('bot.menu.rates') . "\n";
        $message .= "💱 " . __('bot.menu.convert') . "\n";
        $message .= "🏦 " . __('bot.menu.banks') . "\n";
        $message .= "📊 " . __('bot.menu.history') . "\n";
        $message .= "🔔 " . __('bot.menu.alerts') . "\n";
        $message .= "👤 " . __('bot.menu.profile') . "\n\n";
        $message .= __('bot.help.message');

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            MainMenuKeyboard::build($user->language)
        );
    }
}

