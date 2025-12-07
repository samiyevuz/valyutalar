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
        try {
            $message = $this->buildProfileMessage($user);

            if (empty($message)) {
                $message = '❌ ' . __('bot.errors.api_error', locale: $user->language);
            }

            $telegram->sendMessage(
                $update->getChatId(),
                $message,
                ProfileKeyboard::build($user, $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Error in HandleProfileAction', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            try {
                $telegram->sendMessage(
                    $update->getChatId(),
                    '❌ ' . __('bot.errors.api_error', locale: $user->language),
                    ProfileKeyboard::build($user, $user->language)
                );
            } catch (\Exception $sendError) {
                \Log::error('Failed to send error message', ['error' => $sendError->getMessage()]);
            }
        }
    }

    private function buildProfileMessage(TelegramUser $user): string
    {
        try {
            $lang = $user->language ?? 'uz';
            
            $alertsCount = 0;
            try {
                $alertsCount = $user->activeAlerts()->count();
            } catch (\Exception $e) {
                \Log::warning('Failed to get alerts count', ['error' => $e->getMessage()]);
            }
            
            $favorites = [];
            try {
                $favorites = $user->getFavoriteCurrencies();
            } catch (\Exception $e) {
                \Log::warning('Failed to get favorite currencies', ['error' => $e->getMessage()]);
            }
            
            if (empty($favorites)) {
                $favorites = ['-'];
            }

            $digestStatus = $user->daily_digest_enabled
                ? '✅ ' . __('bot.profile.enabled', locale: $lang)
                : '❌ ' . __('bot.profile.disabled', locale: $lang);

            $languageLabel = '-';
            try {
                $languageLabel = $user->getLanguageEnum()->label();
            } catch (\Exception $e) {
                \Log::warning('Failed to get language enum', ['error' => $e->getMessage()]);
            }

            $createdAt = '-';
            try {
                $createdAt = $user->created_at ? $user->created_at->setTimezone('Asia/Tashkent')->format('d.m.Y') : '-';
            } catch (\Exception $e) {
                \Log::warning('Failed to format created_at', ['error' => $e->getMessage()]);
            }

            $lines = [
                '👤 <b>' . __('bot.profile.title', locale: $lang) . '</b>',
                '',
                '📛 ' . __('bot.profile.name', locale: $lang) . ': ' . ($user->getFullName() ?: '-'),
                '🆔 ' . __('bot.profile.username', locale: $lang) . ': ' . ($user->username ? '@' . $user->username : '-'),
                '',
                '🌐 ' . __('bot.profile.language', locale: $lang) . ': ' . $languageLabel,
                '⭐ ' . __('bot.profile.favorites', locale: $lang) . ': ' . implode(', ', $favorites),
                '🔔 ' . __('bot.profile.active_alerts', locale: $lang) . ': ' . $alertsCount,
                '📬 ' . __('bot.profile.daily_digest', locale: $lang) . ': ' . $digestStatus,
                '',
                '📅 ' . __('bot.profile.member_since', locale: $lang) . ': ' . $createdAt,
            ];

            return implode("\n", $lines);
        } catch (\Exception $e) {
            \Log::error('Error building profile message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return '❌ ' . __('bot.errors.api_error', locale: $user->language ?? 'uz');
        }
    }
}

