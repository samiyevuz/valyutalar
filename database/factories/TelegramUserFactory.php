<?php

namespace Database\Factories;

use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TelegramUser>
 */
class TelegramUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->randomNumber(9),
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'language' => fake()->randomElement(['en', 'ru', 'uz']),
            'favorite_currencies' => ['USD', 'EUR'],
            'daily_digest_enabled' => fake()->boolean(),
            'digest_time' => fake()->time(),
            'state' => null,
            'state_data' => null,
            'is_blocked' => false,
            'is_admin' => false,
            'last_activity_at' => now(),
        ];
    }
}

