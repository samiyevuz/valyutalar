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
                        'rate_date' => now()->toDateString(),
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

    public function getBankRates(string $currency = 'USD'): Collection
    {
        $cacheKey = "bank_rates_{$currency}";

        return Cache::remember($cacheKey, 1800, function () use ($currency) {
            return BankRate::getLatestRates($currency);
        });
    }

    public function formatBankRatesMessage(string $currency, string $lang): string
    {
        // Ensure bank rates are fetched
        $this->fetchAllBankRates();
        
        $rates = $this->getBankRates($currency);

        if ($rates->isEmpty()) {
            // Try to fetch rates if empty
            $this->refreshBankRates();
            $rates = $this->getBankRates($currency);
            
            if ($rates->isEmpty()) {
                return __('bot.banks.no_data', locale: $lang);
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
        $lines[] = '<i>' . __('bot.banks.updated_at', ['time' => now()->format('H:i')], $lang) . '</i>';

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

