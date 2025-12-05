<?php

namespace App\Builders\Keyboard;

use App\Models\TelegramUser;

class ProfileKeyboard
{
    public static function build(TelegramUser $user, string $lang = 'en'): array
    {
        $digestButton = $user->daily_digest_enabled
            ? '🔕 ' . __('bot.profile.disable_digest', locale: $lang)
            : '🔔 ' . __('bot.profile.enable_digest', locale: $lang);

        return KeyboardBuilder::inline()
            ->row()
            ->button('🌐 ' . __('bot.profile.change_language', locale: $lang), 'profile:language')
            ->row()
            ->button('⭐ ' . __('bot.profile.edit_favorites', locale: $lang), 'profile:favorites')
            ->row()
            ->button($digestButton, 'profile:toggle_digest')
            ->row()
            ->button('◀️ ' . __('bot.buttons.back', locale: $lang), 'menu:main')
            ->build();
    }

    public static function buildFavoritesEditor(array $currentFavorites, string $lang = 'en'): array
    {
        $currencies = ['USD', 'EUR', 'RUB', 'GBP', 'CNY', 'JPY', 'CHF', 'KZT'];
        $builder = KeyboardBuilder::inline();

        foreach (array_chunk($currencies, 3) as $row) {
            $builder->row();
            foreach ($row as $currency) {
                $isSelected = in_array($currency, $currentFavorites);
                $prefix = $isSelected ? '✅ ' : '⬜ ';
                $builder->button($prefix . $currency, "favorites:toggle:{$currency}");
            }
        }

        $builder->row()
            ->button('💾 ' . __('bot.buttons.save', locale: $lang), 'favorites:save')
            ->button('◀️ ' . __('bot.buttons.cancel', locale: $lang), 'menu:profile');

        return $builder->build();
    }
}

