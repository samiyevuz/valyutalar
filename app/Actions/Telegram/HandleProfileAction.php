<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\ProfileKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\TelegramService;

class HandleProfileAction
{
    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $message = $this->buildProfileMessage($user);

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            ProfileKeyboard::build($user, $user->language)
        );
    }

    private function buildProfileMessage(TelegramUser $user): string
    {
        $lang = $user->language;
        $alertsCount = $user->activeAlerts()->count();
        $favorites = $user->getFavoriteCurrencies();

        $digestStatus = $user->daily_digest_enabled
            ? '✅ ' . __('bot.profile.enabled', locale: $lang)
            : '❌ ' . __('bot.profile.disabled', locale: $lang);

        $lines = [
            '👤 <b>' . __('bot.profile.title', locale: $lang) . '</b>',
            '',
            '📛 ' . __('bot.profile.name', locale: $lang) . ': ' . ($user->getFullName() ?: '-'),
            '🆔 ' . __('bot.profile.username', locale: $lang) . ': ' . ($user->username ? '@' . $user->username : '-'),
            '',
            '🌐 ' . __('bot.profile.language', locale: $lang) . ': ' . $user->getLanguageEnum()->label(),
            '⭐ ' . __('bot.profile.favorites', locale: $lang) . ': ' . implode(', ', $favorites),
            '🔔 ' . __('bot.profile.active_alerts', locale: $lang) . ': ' . $alertsCount,
            '📬 ' . __('bot.profile.daily_digest', locale: $lang) . ': ' . $digestStatus,
            '',
            '📅 ' . __('bot.profile.member_since', locale: $lang) . ': ' . $user->created_at->format('d.m.Y'),
        ];

        return implode("\n", $lines);
    }
}

