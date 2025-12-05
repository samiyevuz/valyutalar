<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BankRatesService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService,
        private BankRatesService $bankRatesService,
    ) {}

    /**
     * Get all live currency rates
     */
    public function rates(): JsonResponse
    {
        $rates = $this->currencyService->getLiveRates();

        $data = array_map(fn($rate) => [
            'currency' => $rate->currencyCode,
            'rate' => $rate->rate,
            'date' => $rate->date->format('Y-m-d'),
            'source' => $rate->source,
        ], $rates);

        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get rate for specific currency
     */
    public function rate(string $currency): JsonResponse
    {
        $rate = $this->currencyService->getRate(strtoupper($currency));

        if (!$rate) {
            return response()->json([
                'success' => false,
                'error' => 'Currency not found',
            ], 404);
        }

        $trend = $this->currencyService->getTrend($currency, 7);

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $rate->currencyCode,
                'rate' => $rate->rate,
                'date' => $rate->date->format('Y-m-d'),
                'source' => $rate->source,
                'trend' => $trend,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Convert currency
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $result = $this->currencyService->convertAmount(
            (float) $validated['amount'],
            $validated['from'],
            $validated['to']
        );

        if ($result->rate <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Conversion failed - invalid currencies',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'amount_from' => $result->amountFrom,
                'currency_from' => $result->currencyFrom,
                'amount_to' => round($result->amountTo, 2),
                'currency_to' => $result->currencyTo,
                'rate' => $result->rate,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get historical rates
     */
    public function history(string $currency, Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $days = min(365, max(1, (int) $days));

        $rates = $this->currencyService->getHistoricalRates(strtoupper($currency), $days);
        $trend = $this->currencyService->getTrend($currency, $days);

        $data = $rates->map(fn($rate) => [
            'date' => $rate->date->format('Y-m-d'),
            'rate' => $rate->rate,
        ])->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => strtoupper($currency),
                'period_days' => $days,
                'rates' => $data,
                'trend' => $trend,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get bank rates
     */
    public function banks(string $currency = 'USD'): JsonResponse
    {
        $rates = $this->bankRatesService->getBankRates(strtoupper($currency));

        $data = $rates->map(fn($rate) => [
            'bank_code' => $rate->bank_code,
            'bank_name' => $rate->bank_name,
            'buy_rate' => (float) $rate->buy_rate,
            'sell_rate' => (float) $rate->sell_rate,
            'spread' => $rate->getSpread(),
            'date' => $rate->rate_date->format('Y-m-d'),
        ])->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => strtoupper($currency),
                'banks' => $data,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

