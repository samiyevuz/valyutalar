<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ChartService
{
    /**
     * Generate a chart URL using QuickChart.io
     */
    public function generateRateChart(Collection $rates, string $currency, int $days): ?string
    {
        if ($rates->isEmpty() || $rates->count() < 2) {
            \Log::warning('Not enough data for chart', [
                'count' => $rates->count(),
                'currency' => $currency,
            ]);
            return null;
        }

        try {
            $chartData = $this->prepareChartData($rates);
            
            // Limit labels to prevent URL too long error
            $maxLabels = 30;
            if (count($chartData['labels']) > $maxLabels) {
                $step = ceil(count($chartData['labels']) / $maxLabels);
                $filteredLabels = [];
                $filteredValues = [];
                for ($i = 0; $i < count($chartData['labels']); $i += $step) {
                    $filteredLabels[] = $chartData['labels'][$i];
                    $filteredValues[] = $chartData['values'][$i];
                }
                $chartData['labels'] = $filteredLabels;
                $chartData['values'] = $filteredValues;
            }

            $minValue = min($chartData['values']);
            $maxValue = max($chartData['values']);
            $range = $maxValue - $minValue;
            
            // Ensure chart has proper scale
            $yMin = max(0, $minValue - ($range * 0.1));
            $yMax = $maxValue + ($range * 0.1);

            $chartConfig = [
                'type' => 'line',
                'data' => [
                    'labels' => $chartData['labels'],
                    'datasets' => [
                        [
                            'label' => "{$currency}/UZS",
                            'data' => $chartData['values'],
                            'fill' => true,
                            'borderColor' => '#2196F3',
                            'backgroundColor' => 'rgba(33, 150, 243, 0.2)',
                            'tension' => 0.4,
                            'pointRadius' => 3,
                            'pointHoverRadius' => 5,
                            'borderWidth' => 2,
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => "{$currency}/UZS - {$days} kun",
                            'font' => ['size' => 16, 'weight' => 'bold'],
                            'color' => '#333',
                        ],
                        'legend' => [
                            'display' => false,
                        ],
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => false,
                            'min' => $yMin,
                            'max' => $yMax,
                            'grid' => [
                                'color' => 'rgba(0,0,0,0.1)',
                                'drawBorder' => true,
                            ],
                            'ticks' => [
                                'color' => '#666',
                                'font' => ['size' => 10],
                            ],
                        ],
                        'x' => [
                            'grid' => ['display' => false],
                            'ticks' => [
                                'color' => '#666',
                                'font' => ['size' => 10],
                                'maxRotation' => 45,
                                'minRotation' => 0,
                            ],
                        ],
                    ],
                ],
            ];

            $jsonConfig = json_encode($chartConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            if (strlen($jsonConfig) > 8000) {
                \Log::warning('Chart config too long, using text chart instead', [
                    'length' => strlen($jsonConfig),
                ]);
                return null;
            }

            $encodedConfig = urlencode($jsonConfig);
            $url = "https://quickchart.io/chart?c={$encodedConfig}&w=800&h=400&bkg=white&f=png";
            
            \Log::info('Chart URL generated', [
                'currency' => $currency,
                'days' => $days,
                'data_points' => count($chartData['values']),
                'url_length' => strlen($url),
            ]);

            return $url;
        } catch (\Exception $e) {
            \Log::error('Error generating chart', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function prepareChartData(Collection $rates): array
    {
        $labels = [];
        $values = [];

        foreach ($rates as $rate) {
            $labels[] = $rate->date->format('d.m');
            $values[] = round($rate->rate, 2);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Generate a simple text-based chart for inline display
     */
    public function generateTextChart(Collection $rates, int $width = 30, int $height = 8): string
    {
        if ($rates->isEmpty()) {
            return 'No data available';
        }

        $values = $rates->pluck('rate')->map(fn($r) => (float) $r)->toArray();

        $min = min($values);
        $max = max($values);
        $range = $max - $min ?: 1;

        $chart = [];

        // Initialize chart with spaces
        for ($y = 0; $y < $height; $y++) {
            $chart[$y] = str_repeat(' ', $width);
        }

        // Plot points
        $step = max(1, count($values) / $width);
        for ($x = 0; $x < $width && $x * $step < count($values); $x++) {
            $value = $values[(int) ($x * $step)];
            $y = $height - 1 - (int) (($value - $min) / $range * ($height - 1));
            $y = max(0, min($height - 1, $y));
            $chart[$y][$x] = '█';
        }

        // Build output
        $result = [];
        $result[] = sprintf('%.0f ┤', $max);

        foreach ($chart as $row) {
            $result[] = '    │' . $row;
        }

        $result[] = sprintf('%.0f ┤', $min);
        $result[] = '    └' . str_repeat('─', $width);

        return '<pre>' . implode("\n", $result) . '</pre>';
    }

    /**
     * Generate trend indicator message
     */
    public function generateTrendMessage(array $trend, string $currency, string $lang): string
    {
        $emoji = match ($trend['trend']) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️',
        };

        $trendText = match ($trend['trend']) {
            'up' => __('bot.history.trend_up', locale: $lang),
            'down' => __('bot.history.trend_down', locale: $lang),
            default => __('bot.history.trend_stable', locale: $lang),
        };

        return sprintf(
            "%s <b>%s</b>: %s (%+.2f%%)",
            $emoji,
            $currency,
            $trendText,
            $trend['change_percent']
        );
    }
}

