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
        $message = "⭐ <b>" . __('bot.favorites.title') . "</b>\n\n";
        $message .= __('bot.favorites.instructions');

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            ProfileKeyboard::buildFavoritesEditor($user->getFavoriteCurrencies(), $user->language)
        );
    }
}

