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
        try {
            $from = strtoupper(trim($from));
            $to = strtoupper(trim($to));
            
            if (empty($from) || empty($to)) {
                Log::warning('Empty currency codes in getConversionRate', [
                    'from' => $from,
                    'to' => $to,
                ]);
                return 0;
            }

            if ($from === $to) {
                return 1.0;
            }

            // If converting to UZS, use direct CBU rate
            if ($to === 'UZS') {
                $rate = $this->getRate($from);
                if (!$rate || !isset($rate->rate) || $rate->rate <= 0) {
                    Log::warning('Invalid rate for conversion to UZS', [
                        'from' => $from,
                        'rate' => $rate,
                    ]);
                    return 0;
                }
                return $rate->rate;
            }

            // If converting from UZS, invert the rate
            if ($from === 'UZS') {
                $rate = $this->getRate($to);
                if (!$rate || !isset($rate->rate) || $rate->rate <= 0) {
                    Log::warning('Invalid rate for conversion from UZS', [
                        'to' => $to,
                        'rate' => $rate,
                    ]);
                    return 0;
                }
                return 1 / $rate->rate;
            }

            // Cross-rate calculation via UZS
            $fromRate = $this->getRate($from);
            $toRate = $this->getRate($to);

            if (!$fromRate || !isset($fromRate->rate) || $fromRate->rate <= 0) {
                Log::warning('Invalid from rate for cross conversion', [
                    'from' => $from,
                    'rate' => $fromRate,
                ]);
                return 0;
            }
            
            if (!$toRate || !isset($toRate->rate) || $toRate->rate <= 0) {
                Log::warning('Invalid to rate for cross conversion', [
                    'to' => $to,
                    'rate' => $toRate,
                ]);
                return 0;
            }

            return $fromRate->rate / $toRate->rate;
        } catch (\Exception $e) {
            Log::error('Error in getConversionRate', [
                'from' => $from ?? 'unknown',
                'to' => $to ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return 0;
        }
    }

    public function getHistoricalRates(string $currency, int $days = 30): Collection
    {
        $currency = strtoupper($currency);
        $cacheKey = "currency_history_{$currency}_{$days}";

        try {
            // Reduce cache time to 5 minutes for more fresh data
            return Cache::remember($cacheKey, 300, function () use ($currency, $days) {
                try {
                    // First, try to get from database
                    $dbRates = CurrencyRate::getHistoricalRates($currency, $days);
                    
                    \Log::info('Historical rates from DB', [
                        'currency' => $currency,
                        'days' => $days,
                        'count' => $dbRates->count(),
                        'expected_min' => max(1, (int)($days * 0.3)),
                    ]);

                    // If we have at least 30% of expected data, return it
                    if ($dbRates->count() >= max(1, (int)($days * 0.3))) {
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
                    $maxApiCalls = min($days, 20); // Limit API calls to prevent timeout
                    $successCount = 0;
                    
                    // Start from most recent dates (more likely to have data)
                    $datesToFetch = [];
                    for ($date = $endDate->copy(); $date >= $startDate; $date->subDay()) {
                        $dateStr = $date->format('Y-m-d');
                        if (!in_array($dateStr, $existingDates)) {
                            $datesToFetch[] = $dateStr;
                        }
                    }
                    
                    // Fetch recent dates first
                    foreach (array_slice($datesToFetch, 0, $maxApiCalls) as $dateStr) {
                        try {
                            $rateData = $this->fetchCbuRatesForDate($dateStr);
                            $fetchedCount++;

                            foreach ($rateData as $rate) {
                                if ($rate->currencyCode === $currency) {
                                    $rates->push($rate);
                                    $this->storeRate($rate);
                                    $successCount++;
                                }
                            }
                            
                            // Small delay to avoid rate limiting
                            if ($fetchedCount % 3 === 0) {
                                usleep(200000); // 0.2 second delay every 3 requests
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to fetch rate for date', [
                                'date' => $dateStr,
                                'currency' => $currency,
                                'error' => $e->getMessage(),
                            ]);
                            // Continue with next date
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
                        'success_count' => $successCount,
                    ]);

                    // If we still don't have enough data, try to get at least some data
                    if ($allRates->isEmpty()) {
                        \Log::warning('No historical rates found, trying to get current rate', [
                            'currency' => $currency,
                        ]);
                        
                        // Try to get current rate as fallback
                        $currentRate = $this->getRate($currency);
                        if ($currentRate) {
                            \Log::info('Using current rate as fallback for history', [
                                'currency' => $currency,
                                'rate' => $currentRate->rate,
                            ]);
                            return collect([$currentRate]);
                        }
                    }

                    return $allRates;
                } catch (\Exception $e) {
                    \Log::error('Error in getHistoricalRates cache callback', [
                        'currency' => $currency,
                        'days' => $days,
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    
                    // Try to return at least database data
                    try {
                        $dbRates = CurrencyRate::getHistoricalRates($currency, $days);
                        if ($dbRates->isNotEmpty()) {
                            return $dbRates->map(fn($r) => new CurrencyRateDTO(
                                currencyCode: $r->currency_code,
                                baseCurrency: $r->base_currency,
                                rate: (float) $r->rate,
                                source: $r->source,
                                date: $r->rate_date,
                            ));
                        }
                    } catch (\Exception $dbError) {
                        \Log::error('Error getting rates from DB as fallback', [
                            'error' => $dbError->getMessage(),
                        ]);
                    }
                    
                    // Last resort: return current rate
                    $currentRate = $this->getRate($currency);
                    if ($currentRate) {
                        return collect([$currentRate]);
                    }
                    
                    return collect();
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error in getHistoricalRates', [
                'currency' => $currency,
                'days' => $days,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Try to get from database without cache
            try {
                $dbRates = CurrencyRate::getHistoricalRates($currency, $days);
                if ($dbRates->isNotEmpty()) {
                    return $dbRates->map(fn($r) => new CurrencyRateDTO(
                        currencyCode: $r->currency_code,
                        baseCurrency: $r->base_currency,
                        rate: (float) $r->rate,
                        source: $r->source,
                        date: $r->rate_date,
                    ));
                }
            } catch (\Exception $dbError) {
                \Log::error('Error getting rates from DB', [
                    'error' => $dbError->getMessage(),
                ]);
            }
            
            // Last resort: return current rate
            $currentRate = $this->getRate($currency);
            if ($currentRate) {
                return collect([$currentRate]);
            }
            
            return collect();
        }
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
            if (!$singleRate || !isset($singleRate->rate)) {
                return [
                    'trend' => 'stable',
                    'change_percent' => 0,
                    'change_absolute' => 0,
                    'oldest_rate' => 0,
                    'latest_rate' => 0,
                ];
            }
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'change_absolute' => 0,
                'oldest_rate' => $singleRate->rate,
                'latest_rate' => $singleRate->rate,
            ];
        }

        $oldestRateObj = $rates->first();
        $latestRateObj = $rates->last();
        
        if (!$oldestRateObj || !isset($oldestRateObj->rate) || !$latestRateObj || !isset($latestRateObj->rate)) {
            return [
                'trend' => 'stable',
                'change_percent' => 0,
                'change_absolute' => 0,
                'oldest_rate' => 0,
                'latest_rate' => 0,
            ];
        }
        
        $oldestRate = $oldestRateObj->rate;
        $latestRate = $latestRateObj->rate;

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
            
            $response = Http::timeout(20)
                ->retry(3, 1000)
                ->get($url);

            if (!$response->successful()) {
                \Log::warning('CBU historical API failed', [
                    'date' => $date,
                    'status' => $response->status(),
                    'url' => $url,
                    'body_preview' => substr($response->body(), 0, 200),
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

