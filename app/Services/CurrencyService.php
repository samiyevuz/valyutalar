<?php

namespace App\Services;

use App\DTOs\ConversionResultDTO;
use App\DTOs\CurrencyRateDTO;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    private string $cbuUrl = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';

    public function getLiveRates(): array
    {
        $cacheKey = 'currency_live_rates';

        return Cache::remember($cacheKey, config('currency.cache.ttl', 1800), function () {
            return $this->fetchCbuRates();
        });
    }

    public function getRate(string $currency): ?CurrencyRateDTO
    {
        $rates = $this->getLiveRates();
        $currency = strtoupper($currency);

        foreach ($rates as $rate) {
            if ($rate->currencyCode === $currency) {
                return $rate;
            }
        }

        return null;
    }

    public function convertAmount(float $amount, string $from, string $to): ConversionResultDTO
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $rate = $this->getConversionRate($from, $to);
        $convertedAmount = $amount * $rate;

        return new ConversionResultDTO(
            amountFrom: $amount,
            currencyFrom: $from,
            amountTo: $convertedAmount,
            currencyTo: $to,
            rate: $rate,
            timestamp: now(),
        );
    }

    public function getConversionRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        // If converting to UZS, use direct CBU rate
        if ($to === 'UZS') {
            $rate = $this->getRate($from);
            return $rate ? $rate->rate : 0;
        }

        // If converting from UZS, invert the rate
        if ($from === 'UZS') {
            $rate = $this->getRate($to);
            return $rate && $rate->rate > 0 ? 1 / $rate->rate : 0;
        }

        // Cross-rate calculation via UZS
        $fromRate = $this->getRate($from);
        $toRate = $this->getRate($to);

        if ($fromRate && $toRate && $toRate->rate > 0) {
            return $fromRate->rate / $toRate->rate;
        }

        return 0;
    }

    public function getHistoricalRates(string $currency, int $days = 30): Collection
    {
        $currency = strtoupper($currency);
        $cacheKey = "currency_history_{$currency}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($currency, $days) {
            // First, try to get from database
            $dbRates = CurrencyRate::getHistoricalRates($currency, $days);

            if ($dbRates->count() >= $days * 0.7) {
                return $dbRates->map(fn($r) => new CurrencyRateDTO(
                    currencyCode: $r->currency_code,
                    baseCurrency: $r->base_currency,
                    rate: (float) $r->rate,
                    source: $r->source,
                    date: $r->rate_date,
                ));
            }

            // Fetch from API for missing dates
            $rates = collect();
            $endDate = now();
            $startDate = now()->subDays($days);

            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $rateData = $this->fetchCbuRatesForDate($dateStr);

                foreach ($rateData as $rate) {
                    if ($rate->currencyCode === $currency) {
                        $rates->push($rate);
                        $this->storeRate($rate);
                    }
                }
            }

            return $rates;
        });
    }

    public function getTrend(string $currency, int $days = 7): array
    {
        $rates = $this->getHistoricalRates($currency, $days);

        if ($rates->count() < 2) {
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'change_absolute' => 0,
                'oldest_rate' => 0,
                'latest_rate' => 0,
            ];
        }

        $oldestRate = $rates->first()->rate;
        $latestRate = $rates->last()->rate;

        $changeAbsolute = $latestRate - $oldestRate;
        $changePercent = $oldestRate > 0 ? ($changeAbsolute / $oldestRate) * 100 : 0;

        $trend = match (true) {
            $changePercent > 0.1 => 'up',
            $changePercent < -0.1 => 'down',
            default => 'stable',
        };

        return [
            'trend' => $trend,
            'change_percent' => round($changePercent, 2),
            'change_absolute' => round($changeAbsolute, 2),
            'oldest_rate' => $oldestRate,
            'latest_rate' => $latestRate,
        ];
    }

    public function formatRatesMessage(array $currencies, string $lang): string
    {
        $rates = $this->getLiveRates();
        $filtered = array_filter($rates, fn($r) => in_array($r->currencyCode, $currencies));

        $lines = [__('bot.rates.title', locale: $lang)];
        $lines[] = '';

        foreach ($filtered as $rate) {
            $trend = $this->getTrend($rate->currencyCode, 1);
            $emoji = match ($trend['trend']) {
                'up' => '📈',
                'down' => '📉',
                default => '➡️',
            };

            $change = $trend['change_percent'] != 0
                ? sprintf(' (%+.2f%%)', $trend['change_percent'])
                : '';

            $lines[] = sprintf(
                '%s <b>%s</b>: %s UZS%s',
                $emoji,
                $rate->currencyCode,
                number_format($rate->rate, 2, '.', ' '),
                $change
            );
        }

        $lines[] = '';
        $lines[] = '<i>' . __('bot.rates.updated_at', ['time' => now()->format('H:i')], $lang) . '</i>';

        return implode("\n", $lines);
    }

    private function fetchCbuRates(): array
    {
        try {
            $response = Http::timeout(10)->get($this->cbuUrl);

            if (!$response->successful()) {
                Log::error('CBU API error', [
                    'status' => $response->status(),
                    'url' => $this->cbuUrl,
                    'body' => substr($response->body(), 0, 200),
                ]);
                return $this->getFallbackRates();
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                Log::warning('CBU API returned invalid data', [
                    'type' => gettype($data),
                    'count' => is_array($data) ? count($data) : 0,
                ]);
                return $this->getFallbackRates();
            }

            $rates = [];

            foreach ($data as $item) {
                if (!isset($item['Ccy']) || !isset($item['Rate'])) {
                    continue;
                }

                try {
                    $rate = CurrencyRateDTO::fromCbuData($item);
                    $rates[] = $rate;
                    $this->storeRate($rate);
                } catch (\Exception $e) {
                    Log::warning('Failed to parse CBU rate', [
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (empty($rates)) {
                Log::warning('No rates parsed from CBU API');
                return $this->getFallbackRates();
            }

            return $rates;
        } catch (\Exception $e) {
            Log::error('CBU API exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->getFallbackRates();
        }
    }

    private function fetchCbuRatesForDate(string $date): array
    {
        try {
            $url = $this->cbuUrl . $date . '/';
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                return [];
            }

            $rates = [];

            foreach ($data as $item) {
                $rates[] = CurrencyRateDTO::fromCbuData($item);
            }

            return $rates;
        } catch (\Exception $e) {
            Log::error('CBU historical API exception', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function storeRate(CurrencyRateDTO $rate): void
    {
        try {
            CurrencyRate::updateOrCreate(
                [
                    'currency_code' => $rate->currencyCode,
                    'rate_date' => $rate->date->format('Y-m-d'),
                    'source' => 'cbu',
                ],
                [
                    'base_currency' => 'UZS',
                    'rate' => $rate->rate,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to store currency rate', [
                'currency' => $rate->currencyCode,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getFallbackRates(): array
    {
        // Return rates from database if API fails
        $latestDate = CurrencyRate::max('rate_date');
        
        if (!$latestDate) {
            return [];
        }

        $latestRates = CurrencyRate::where('rate_date', $latestDate)
            ->fromSource('cbu')
            ->get();

        return $latestRates->map(fn($r) => new CurrencyRateDTO(
            currencyCode: $r->currency_code,
            baseCurrency: $r->base_currency,
            rate: (float) $r->rate,
            source: $r->source,
            date: $r->rate_date,
        ))->toArray();
    }

    public function refreshRates(): void
    {
        Cache::forget('currency_live_rates');
        $this->getLiveRates();
    }
}

