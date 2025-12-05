<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ChartService
{
    /**
     * Generate a chart URL using QuickChart.io
     */
    public function generateRateChart(Collection $rates, string $currency, int $days): string
    {
        if ($rates->isEmpty()) {
            return '';
        }

        $chartData = $this->prepareChartData($rates);

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
                        'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                        'tension' => 0.3,
                        'pointRadius' => 2,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => "{$currency}/UZS - {$days} days",
                        'font' => ['size' => 14],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => false,
                        'grid' => ['color' => 'rgba(0,0,0,0.1)'],
                    ],
                    'x' => [
                        'grid' => ['display' => false],
                    ],
                ],
            ],
        ];

        $encodedConfig = urlencode(json_encode($chartConfig));

        return "https://quickchart.io/chart?c={$encodedConfig}&w=600&h=300&bkg=white";
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

