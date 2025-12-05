<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversionHistory>
 */
class ConversionHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'telegram_user_id' => TelegramUser::factory(),
            'currency_from' => fake()->randomElement(['USD', 'EUR', 'RUB']),
            'currency_to' => 'UZS',
            'amount_from' => fake()->randomFloat(2, 1, 1000),
            'amount_to' => fake()->randomFloat(2, 10000, 15000000),
            'rate_used' => fake()->randomFloat(6, 10000, 15000),
        ];
    }
}

