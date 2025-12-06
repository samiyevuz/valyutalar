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
        $message = __('bot.help.message', locale: $user->language);

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            MainMenuKeyboard::build($user->language)
        );
    }
}

