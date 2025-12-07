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
        try {
            $favorites = [];
            try {
                $favorites = $user->getFavoriteCurrencies();
            } catch (\Exception $e) {
                \Log::warning('Failed to get favorite currencies', ['error' => $e->getMessage()]);
            }
            
            if (empty($favorites)) {
                $favorites = [];
            }
            
            $message = "⭐ <b>" . __('bot.favorites.title', locale: $user->language) . "</b>\n\n";
            $message .= __('bot.favorites.select', locale: $user->language) . "\n\n";
            $message .= __('bot.favorites.current', locale: $user->language) . ": " . (empty($favorites) ? '-' : implode(', ', $favorites));

            $telegram->sendMessage(
                $update->getChatId(),
                $message,
                ProfileKeyboard::buildFavoritesEditor($favorites, $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Error in HandleFavoritesAction', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            try {
                $telegram->sendMessage(
                    $update->getChatId(),
                    '❌ ' . __('bot.errors.api_error', locale: $user->language),
                    ProfileKeyboard::buildFavoritesEditor([], $user->language)
                );
            } catch (\Exception $sendError) {
                \Log::error('Failed to send error message', ['error' => $sendError->getMessage()]);
            }
        }
    }
}

