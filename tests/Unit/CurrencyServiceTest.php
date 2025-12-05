<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CurrencyService::class);
        Cache::flush();
    }

    public function test_can_get_live_rates(): void
    {
        Http::fake([
            'cbu.uz/*' => Http::response([
                [
                    'Ccy' => 'USD',
                    'Rate' => '12500.00',
                    'Date' => now()->format('Y-m-d'),
                ],
                [
                    'Ccy' => 'EUR',
                    'Rate' => '13500.00',
                    'Date' => now()->format('Y-m-d'),
                ],
            ]),
        ]);

        $rates = $this->service->getLiveRates();

        $this->assertNotEmpty($rates);
        $this->assertArrayHasKey(0, $rates);
    }

    public function test_can_get_specific_currency_rate(): void
    {
        Http::fake([
            'cbu.uz/*' => Http::response([
                [
                    'Ccy' => 'USD',
                    'Rate' => '12500.00',
                    'Date' => now()->format('Y-m-d'),
                ],
            ]),
        ]);

        $rate = $this->service->getRate('USD');

        $this->assertNotNull($rate);
        $this->assertEquals('USD', $rate->currencyCode);
    }

    public function test_can_convert_amount(): void
    {
        Http::fake([
            'cbu.uz/*' => Http::response([
                [
                    'Ccy' => 'USD',
                    'Rate' => '12500.00',
                    'Date' => now()->format('Y-m-d'),
                ],
            ]),
        ]);

        $result = $this->service->convertAmount(100, 'USD', 'UZS');

        $this->assertEquals(100, $result->amountFrom);
        $this->assertEquals('USD', $result->currencyFrom);
        $this->assertEquals('UZS', $result->currencyTo);
        $this->assertGreaterThan(0, $result->amountTo);
    }

    public function test_can_get_trend(): void
    {
        Http::fake([
            'cbu.uz/*' => Http::response([
                [
                    'Ccy' => 'USD',
                    'Rate' => '12500.00',
                    'Date' => now()->format('Y-m-d'),
                ],
            ]),
        ]);

        $trend = $this->service->getTrend('USD', 7);

        $this->assertIsArray($trend);
        $this->assertArrayHasKey('trend', $trend);
        $this->assertArrayHasKey('change_percent', $trend);
    }
}
