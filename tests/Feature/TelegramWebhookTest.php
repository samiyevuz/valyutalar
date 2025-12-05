<?php

namespace Tests\Feature;

use App\Models\TelegramUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_accepts_valid_update(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $update = [
            'update_id' => 123,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123456,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 123456,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
                'entities' => [
                    [
                        'offset' => 0,
                        'length' => 6,
                        'type' => 'bot_command',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/telegram/webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_webhook_creates_user_on_first_message(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $update = [
            'update_id' => 123,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 999999,
                    'is_bot' => false,
                    'first_name' => 'New',
                    'username' => 'newuser',
                ],
                'chat' => [
                    'id' => 999999,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
                'entities' => [
                    [
                        'offset' => 0,
                        'length' => 6,
                        'type' => 'bot_command',
                    ],
                ],
            ],
        ];

        $this->assertDatabaseMissing('telegram_users', ['telegram_id' => 999999]);

        $this->postJson('/telegram/webhook', $update);

        $this->assertDatabaseHas('telegram_users', ['telegram_id' => 999999]);
    }
}
