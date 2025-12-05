<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankRate>
 */
class BankRateFactory extends Factory
{
    public function definition(): array
    {
        $baseRate = fake()->randomFloat(2, 12000, 13000);

        return [
            'bank_code' => fake()->randomElement(['uzum', 'kapitalbank', 'trastbank']),
            'bank_name' => fake()->company(),
            'currency_code' => fake()->randomElement(['USD', 'EUR', 'RUB']),
            'buy_rate' => $baseRate - fake()->randomFloat(2, 10, 50),
            'sell_rate' => $baseRate + fake()->randomFloat(2, 10, 50),
            'rate_date' => now()->toDateString(),
        ];
    }
}

