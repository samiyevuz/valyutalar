<?php

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    private TelegramService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TelegramService::class);
    }

    public function test_can_send_message(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 123,
                    'chat' => ['id' => 456],
                ],
            ]),
        ]);

        $result = $this->service->sendMessage(123456, 'Test message');

        $this->assertTrue($result['ok'] ?? false);
    }

    public function test_can_set_webhook(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => true,
            ]),
        ]);

        $result = $this->service->setWebhook('https://example.com/webhook');

        $this->assertTrue($result['ok'] ?? false);
    }

    public function test_can_get_webhook_info(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'url' => 'https://example.com/webhook',
                    'pending_update_count' => 0,
                ],
            ]),
        ]);

        $result = $this->service->getWebhookInfo();

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
    }
}
