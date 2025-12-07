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
            timestamp: now('Asia/Tashkent'),
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

        // Reduce cache time to 5 minutes for more fresh data
        return Cache::remember($cacheKey, 300, function () use ($currency, $days) {
            // First, try to get from database
            $dbRates = CurrencyRate::getHistoricalRates($currency, $days);
            
            \Log::info('Historical rates from DB', [
                'currency' => $currency,
                'days' => $days,
                'count' => $dbRates->count(),
                'expected_min' => (int)($days * 0.5),
            ]);

            // If we have at least 50% of expected data, return it
            if ($dbRates->count() >= max(1, (int)($days * 0.5))) {
                $mappedRates = $dbRates->map(fn($r) => new CurrencyRateDTO(
                    currencyCode: $r->currency_code,
                    baseCurrency: $r->base_currency,
                    rate: (float) $r->rate,
                    source: $r->source,
                    date: $r->rate_date,
                ));
                
                \Log::info('Returning rates from DB', [
                    'currency' => $currency,
                    'count' => $mappedRates->count(),
                ]);
                
                return $mappedRates;
            }

            // Fetch from API for missing dates
            \Log::info('Fetching historical rates from API', [
                'currency' => $currency,
                'days' => $days,
                'db_count' => $dbRates->count(),
            ]);

            $rates = collect();
            $endDate = now('Asia/Tashkent');
            $startDate = now('Asia/Tashkent')->subDays($days);
            
            // Get existing dates from DB to avoid duplicate API calls
            $existingDates = $dbRates->pluck('rate_date')->map(fn($d) => $d->format('Y-m-d'))->toArray();

            $fetchedCount = 0;
            $maxApiCalls = min($days, 30); // Limit API calls to prevent timeout
            
            for ($date = $startDate->copy(); $date <= $endDate && $fetchedCount < $maxApiCalls; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                
                // Skip if we already have this date in DB
                if (in_array($dateStr, $existingDates)) {
                    continue;
                }
                
                $rateData = $this->fetchCbuRatesForDate($dateStr);
                $fetchedCount++;

                foreach ($rateData as $rate) {
                    if ($rate->currencyCode === $currency) {
                        $rates->push($rate);
                        $this->storeRate($rate);
                    }
                }
                
                // Small delay to avoid rate limiting
                if ($fetchedCount % 5 === 0) {
                    usleep(100000); // 0.1 second delay every 5 requests
                }
            }

            // Merge DB rates with newly fetched rates
            $allRates = $dbRates->map(fn($r) => new CurrencyRateDTO(
                currencyCode: $r->currency_code,
                baseCurrency: $r->base_currency,
                rate: (float) $r->rate,
                source: $r->source,
                date: $r->rate_date,
            ))->merge($rates)->unique(function ($rate) {
                return $rate->currencyCode . '_' . $rate->date->format('Y-m-d');
            })->sortBy('date')->values();

            \Log::info('Historical rates fetched', [
                'currency' => $currency,
                'total_count' => $allRates->count(),
                'from_db' => $dbRates->count(),
                'from_api' => $rates->count(),
            ]);

            return $allRates;
        });
    }

    public function getTrend(string $currency, int $days = 7): array
    {
        $rates = $this->getHistoricalRates($currency, $days);

        if ($rates->isEmpty()) {
            // Try to get current rate as fallback
            $currentRate = $this->getRate($currency);
            if ($currentRate) {
                return [
                    'trend' => 'stable',
                    'change_percent' => 0,
                    'change_absolute' => 0,
                    'oldest_rate' => $currentRate->rate,
                    'latest_rate' => $currentRate->rate,
                ];
            }
            
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'change_absolute' => 0,
                'oldest_rate' => 0,
                'latest_rate' => 0,
            ];
        }

        if ($rates->count() < 2) {
            $singleRate = $rates->first();
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'change_absolute' => 0,
                'oldest_rate' => $singleRate->rate,
                'latest_rate' => $singleRate->rate,
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
        $lines[] = '<i>' . __('bot.rates.updated_at', ['time' => now('Asia/Tashkent')->format('d.m.Y H:i')], $lang) . '</i>';

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
            // CBU API format: https://cbu.uz/ru/arkhiv-kursov-valyut/json/YYYY-MM-DD/
            $url = $this->cbuUrl . $date . '/';
            
            \Log::debug('Fetching CBU rates for date', [
                'date' => $date,
                'url' => $url,
            ]);
            
            $response = Http::timeout(15)
                ->retry(2, 500)
                ->get($url);

            if (!$response->successful()) {
                \Log::warning('CBU historical API failed', [
                    'date' => $date,
                    'status' => $response->status(),
                    'url' => $url,
                ]);
                return [];
            }

            $data = $response->json();

            if (!is_array($data) || empty($data)) {
                \Log::warning('CBU historical API returned invalid data', [
                    'date' => $date,
                    'type' => gettype($data),
                    'count' => is_array($data) ? count($data) : 0,
                ]);
                return [];
            }

            $rates = [];

            foreach ($data as $item) {
                // Validate required fields
                if (!isset($item['Ccy']) || !isset($item['Rate'])) {
                    continue;
                }

                try {
                    $rate = CurrencyRateDTO::fromCbuData($item);
                    $rates[] = $rate;
                } catch (\Exception $e) {
                    \Log::warning('Failed to parse CBU historical rate', [
                        'date' => $date,
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            \Log::debug('CBU rates fetched for date', [
                'date' => $date,
                'count' => count($rates),
            ]);

            return $rates;
        } catch (\Exception $e) {
            \Log::error('CBU historical API exception', [
                'date' => $date,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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

