<?php

namespace App\Builders\Keyboard;

class MainMenuKeyboard
{
    public static function build(string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('💱 ' . __('bot.menu.rates', locale: $lang), 'menu:rates')
            ->button('🔄 ' . __('bot.menu.convert', locale: $lang), 'menu:convert')
            ->row()
            ->button('🏦 ' . __('bot.menu.banks', locale: $lang), 'menu:banks')
            ->button('📊 ' . __('bot.menu.history', locale: $lang), 'menu:history')
            ->row()
            ->button('🔔 ' . __('bot.menu.alerts', locale: $lang), 'menu:alerts')
            ->build();
    }

    public static function buildCompact(string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('💱', 'menu:rates')
            ->button('🔄', 'menu:convert')
            ->button('🏦', 'menu:banks')
            ->button('📊', 'menu:history')
            ->row()
            ->button('🔔', 'menu:alerts')
            ->build();
    }
}

