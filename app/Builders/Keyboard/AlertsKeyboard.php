<?php

namespace App\Builders\Keyboard;

use Illuminate\Support\Collection;

class AlertsKeyboard
{
    public static function build(Collection $alerts, string $lang = 'en'): array
    {
        $builder = KeyboardBuilder::inline()
            ->row()
            ->button('➕ ' . __('bot.alerts.create', locale: $lang), 'alerts:create');

        if ($alerts->isNotEmpty()) {
            $builder->row()
                ->button('🗑️ ' . __('bot.alerts.delete', locale: $lang), 'alerts:delete_menu');
        }

        $builder->row()->button('◀️ ' . __('bot.buttons.back', locale: $lang), 'menu:main');

        return $builder->build();
    }

    public static function buildDeleteMenu(Collection $alerts, string $lang = 'en'): array
    {
        $builder = KeyboardBuilder::inline();

        foreach ($alerts as $alert) {
            $builder->row()->button(
                '❌ ' . $alert->getDescription(),
                "alerts:delete:{$alert->id}"
            );
        }

        $builder->row()->button('◀️ ' . __('bot.buttons.back', locale: $lang), 'menu:alerts');

        return $builder->build();
    }

    public static function buildCurrencySelector(string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('🇺🇸 USD', 'alerts:currency:USD')
            ->button('🇪🇺 EUR', 'alerts:currency:EUR')
            ->row()
            ->button('🇷🇺 RUB', 'alerts:currency:RUB')
            ->button('🇬🇧 GBP', 'alerts:currency:GBP')
            ->row()
            ->button('◀️ ' . __('bot.buttons.cancel', locale: $lang), 'menu:alerts')
            ->build();
    }

    public static function buildConditionSelector(string $currency, string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('📈 ' . __('bot.alerts.when_above', locale: $lang), "alerts:condition:{$currency}:above")
            ->row()
            ->button('📉 ' . __('bot.alerts.when_below', locale: $lang), "alerts:condition:{$currency}:below")
            ->row()
            ->button('◀️ ' . __('bot.buttons.cancel', locale: $lang), 'alerts:create')
            ->build();
    }

    public static function buildConfirmation(int $alertId, string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button('✅ ' . __('bot.buttons.confirm', locale: $lang), "alerts:confirm_delete:{$alertId}")
            ->button('❌ ' . __('bot.buttons.cancel', locale: $lang), 'menu:alerts')
            ->build();
    }
}

