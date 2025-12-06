<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\ProfileKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\TelegramService;

class HandleFavoritesAction
{
    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $favorites = $user->getFavoriteCurrencies();
        $message = "⭐ <b>" . __('bot.favorites.title', locale: $user->language) . "</b>\n\n";
        $message .= __('bot.favorites.select', locale: $user->language) . "\n\n";
        $message .= __('bot.favorites.current', locale: $user->language) . ": " . implode(', ', $favorites);

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            ProfileKeyboard::buildFavoritesEditor($favorites, $user->language)
        );
    }
}

