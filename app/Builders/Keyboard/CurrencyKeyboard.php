<?php

namespace App\Builders\Keyboard;

use App\Enums\Currency;

class CurrencyKeyboard
{
    public static function build(string $action = 'rate', string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button(Currency::USD->flag() . ' USD', "{$action}:USD")
            ->button(Currency::EUR->flag() . ' EUR', "{$action}:EUR")
            ->row()
            ->button(Currency::RUB->flag() . ' RUB', "{$action}:RUB")
            ->button(Currency::GBP->flag() . ' GBP', "{$action}:GBP")
            ->row()
            ->button('📊 ' . __('bot.buttons.all_rates', locale: $lang), "{$action}:all")
            ->row()
            ->button('🏠 ' . __('bot.buttons.main_menu', locale: $lang), 'menu:main')
            ->build();
    }

    public static function buildForConversion(string $direction = 'from', string $lang = 'en'): array
    {
        $currencies = Currency::main();
        $builder = KeyboardBuilder::inline();

        // Add UZS for conversion
        $allCurrencies = [...$currencies, Currency::UZS];

        foreach (array_chunk($allCurrencies, 2) as $chunk) {
            $builder->row();
            foreach ($chunk as $currency) {
                $builder->button(
                    $currency->flag() . ' ' . $currency->value,
                    "convert:{$direction}:{$currency->value}"
                );
            }
        }

        $builder->row()->button('🏠 ' . __('bot.buttons.main_menu', locale: $lang), 'menu:main');

        return $builder->build();
    }


    public static function buildForBanks(string $lang = 'en'): array
    {
        return KeyboardBuilder::inline()
            ->row()
            ->button(Currency::USD->flag() . ' USD', 'banks:USD')
            ->button(Currency::EUR->flag() . ' EUR', 'banks:EUR')
            ->row()
            ->button(Currency::RUB->flag() . ' RUB', 'banks:RUB')
            ->row()
            ->button('🏠 ' . __('bot.buttons.main_menu', locale: $lang), 'menu:main')
            ->build();
    }
}

