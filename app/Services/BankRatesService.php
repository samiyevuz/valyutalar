<?php

namespace App\Services;

use App\Models\BankRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankRatesService
{
    private array $banks;

    public function __construct()
    {
        $this->banks = config('currency.banks', []);
    }

    public function fetchAllBankRates(): int
    {
        $fetched = 0;

        // For demo purposes, we'll create sample bank rates
        // In production, each bank would have its own API integration
        $this->fetchSampleBankRates();
        $fetched = count($this->banks);

        return $fetched;
    }

    private function fetchSampleBankRates(): void
    {
        // Get CBU base rate for reference
        $cbuRates = $this->getCbuReferenceRates();

        foreach ($this->banks as $code => $config) {
            foreach (['USD', 'EUR', 'RUB'] as $currency) {
                $baseRate = $cbuRates[$currency] ?? 0;

                if ($baseRate <= 0) {
                    continue;
                }

                // Simulate bank spread (buy lower, sell higher than CBU)
                $spreadPercent = $this->getBankSpread($code);
                $buyRate = $baseRate * (1 - $spreadPercent / 100);
                $sellRate = $baseRate * (1 + $spreadPercent / 100);

                BankRate::updateOrCreate(
                    [
                        'bank_code' => $code,
                        'currency_code' => $currency,
                        'rate_date' => now('Asia/Tashkent')->toDateString(),
                    ],
                    [
                        'bank_name' => $config['name'],
                        'buy_rate' => $buyRate,
                        'sell_rate' => $sellRate,
                    ]
                );
            }
        }
    }

    private function getCbuReferenceRates(): array
    {
        try {
            $response = Http::timeout(10)->get('https://cbu.uz/ru/arkhiv-kursov-valyut/json/');

            if (!$response->successful()) {
                return [];
            }

            $rates = [];
            foreach ($response->json() as $item) {
                $rates[$item['Ccy']] = (float) $item['Rate'];
            }

            return $rates;
        } catch (\Exception $e) {
            Log::error('Failed to fetch CBU rates for bank reference', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function getBankSpread(string $bankCode): float
    {
        // Different banks have different spreads
        $spreads = [
            'uzum' => 0.3,
            'ipak_yuli' => 0.4,
            'kapitalbank' => 0.35,
            'trastbank' => 0.45,
            'hamkorbank' => 0.5,
            'tbc' => 0.35,
            'nbu' => 0.25,
            'asakabank' => 0.4,
        ];

        return $spreads[$bankCode] ?? 0.5;
    }

    public function getBankRates(string $currency = 'USD', bool $forceRefresh = false): Collection
    {
        $cacheKey = "bank_rates_{$currency}";

        // If force refresh, clear cache first
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Cache for only 5 minutes (300 seconds) for real-time data
        return Cache::remember($cacheKey, 300, function () use ($currency) {
            return BankRate::getLatestRates($currency);
        });
    }

    public function formatBankRatesMessage(string $currency, string $lang): string
    {
        // Always fetch fresh bank rates for real-time data
        $this->fetchAllBankRates();
        
        // Force refresh to get latest data
        $rates = $this->getBankRates($currency, true);

        if ($rates->isEmpty()) {
            // Try to fetch rates if empty
            $this->refreshBankRates();
            $rates = $this->getBankRates($currency, true);
            
            if ($rates->isEmpty()) {
                return __('bot.banks.no_data', locale: $lang);
            }
        }
        
        // Get the latest update time from the rates (GMT+5 - Uzbekistan time)
        $latestUpdateTime = now('Asia/Tashkent');
        if ($rates->isNotEmpty()) {
            // Try to get updated_at or created_at from the first rate
            $firstRate = $rates->first();
            if (isset($firstRate->updated_at)) {
                $latestUpdateTime = \Carbon\Carbon::parse($firstRate->updated_at)->setTimezone('Asia/Tashkent');
            } elseif (isset($firstRate->created_at)) {
                $latestUpdateTime = \Carbon\Carbon::parse($firstRate->created_at)->setTimezone('Asia/Tashkent');
            } elseif (isset($firstRate->rate_date)) {
                $latestUpdateTime = \Carbon\Carbon::parse($firstRate->rate_date)
                    ->setTimezone('Asia/Tashkent')
                    ->setTime(now('Asia/Tashkent')->hour, now('Asia/Tashkent')->minute);
            }
        }

        $lines = [
            '🏦 <b>' . __('bot.banks.title', ['currency' => $currency], $lang) . '</b>',
            '',
        ];

        // Table header
        $lines[] = '<pre>';
        $lines[] = sprintf(
            '%-12s │ %8s │ %8s',
            __('bot.banks.bank', locale: $lang),
            __('bot.banks.buy', locale: $lang),
            __('bot.banks.sell', locale: $lang)
        );
        $lines[] = str_repeat('─', 35);

        foreach ($rates as $rate) {
            $bankName = mb_substr($rate->bank_name, 0, 12);
            $lines[] = sprintf(
                '%-12s │ %8s │ %8s',
                $bankName,
                number_format((float) $rate->buy_rate, 0, '.', ' '),
                number_format((float) $rate->sell_rate, 0, '.', ' ')
            );
        }

        $lines[] = '</pre>';
        $lines[] = '';

        // Best rates
        $bestBuy = $rates->sortByDesc('buy_rate')->first();
        $bestSell = $rates->sortBy('sell_rate')->first();

        if ($bestBuy) {
            $lines[] = '💰 <b>' . __('bot.banks.best_buy', locale: $lang) . ':</b>';
            $lines[] = sprintf(
                '   %s - %s UZS',
                $bestBuy->bank_name,
                number_format((float) $bestBuy->buy_rate, 0, '.', ' ')
            );
        }

        if ($bestSell) {
            $lines[] = '💳 <b>' . __('bot.banks.best_sell', locale: $lang) . ':</b>';
            $lines[] = sprintf(
                '   %s - %s UZS',
                $bestSell->bank_name,
                number_format((float) $bestSell->sell_rate, 0, '.', ' ')
            );
        }

        $lines[] = '';
        // Show exact update time with date and time
        $updateTime = $latestUpdateTime->format('d.m.Y H:i');
        $lines[] = '<i>🕐 ' . __('bot.banks.updated_at', ['time' => $updateTime], $lang) . '</i>';

        return implode("\n", $lines);
    }

    public function refreshBankRates(): void
    {
        Cache::forget('bank_rates_USD');
        Cache::forget('bank_rates_EUR');
        Cache::forget('bank_rates_RUB');

        $this->fetchAllBankRates();
    }
}

