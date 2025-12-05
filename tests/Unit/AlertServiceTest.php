<?php

namespace Tests\Unit;

use App\Models\Alert;
use App\Models\TelegramUser;
use App\Services\AlertService;
use App\Services\CurrencyService;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AlertService::class);
    }

    public function test_can_create_alert(): void
    {
        $user = TelegramUser::factory()->create();

        $alert = $this->service->createAlert(
            $user,
            'USD',
            'UZS',
            'above',
            12500.00
        );

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertEquals($user->id, $alert->telegram_user_id);
        $this->assertEquals('USD', $alert->currency_from);
        $this->assertEquals('UZS', $alert->currency_to);
        $this->assertEquals('above', $alert->condition);
        $this->assertEquals(12500.00, $alert->target_rate);
    }

    public function test_can_parse_alert_from_text(): void
    {
        $user = TelegramUser::factory()->create();

        $alert = $this->service->parseAndCreateAlert('USD > 12500', $user);

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertEquals('USD', $alert->currency_from);
        $this->assertEquals('above', $alert->condition);
    }

    public function test_can_get_user_alerts(): void
    {
        $user = TelegramUser::factory()->create();

        Alert::factory()->count(3)->create([
            'telegram_user_id' => $user->id,
            'is_active' => true,
            'is_triggered' => false,
        ]);

        $alerts = $this->service->getUserAlerts($user);

        $this->assertCount(3, $alerts);
    }

    public function test_can_delete_alert(): void
    {
        $user = TelegramUser::factory()->create();
        $alert = Alert::factory()->create([
            'telegram_user_id' => $user->id,
        ]);

        $result = $this->service->deleteAlert($alert->id, $user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('alerts', ['id' => $alert->id]);
    }
}
