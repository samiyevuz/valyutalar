<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'telegram_user_id' => TelegramUser::factory(),
            'currency_from' => fake()->randomElement(['USD', 'EUR', 'RUB']),
            'currency_to' => 'UZS',
            'condition' => fake()->randomElement(['above', 'below']),
            'target_rate' => fake()->randomFloat(2, 10000, 20000),
            'is_active' => true,
            'is_triggered' => false,
            'triggered_at' => null,
            'triggered_rate' => null,
        ];
    }
}

