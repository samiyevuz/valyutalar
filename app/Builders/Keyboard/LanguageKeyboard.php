<?php

namespace App\Builders\Keyboard;

use App\Enums\Language;

class LanguageKeyboard
{
    public static function build(): array
    {
        $builder = KeyboardBuilder::inline();

        foreach (Language::all() as $language) {
            $builder->row()->button($language->label(), 'lang:' . $language->value);
        }

        return $builder->build();
    }

    public static function buildInline(): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('🇬🇧', 'lang:en')
            ->button('🇷🇺', 'lang:ru')
            ->button('🇺🇿', 'lang:uz')
            ->build();
    }

    public static function buildWithBack(string $lang = 'en'): array
    {
        $builder = KeyboardBuilder::inline();

        foreach (Language::all() as $language) {
            $builder->row()->button($language->label(), 'lang:' . $language->value);
        }

        $builder->row()->button('◀️ ' . __('bot.buttons.back', locale: $lang), 'menu:profile');

        return $builder->build();
    }
}

