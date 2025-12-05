<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CurrencyRate>
 */
class CurrencyRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'currency_code' => fake()->randomElement(['USD', 'EUR', 'RUB', 'GBP']),
            'base_currency' => 'UZS',
            'rate' => fake()->randomFloat(2, 10000, 20000),
            'source' => 'cbu',
            'rate_date' => fake()->date(),
        ];
    }
}

