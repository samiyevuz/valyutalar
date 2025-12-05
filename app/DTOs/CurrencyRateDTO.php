<?php

namespace App\DTOs;

use Carbon\Carbon;
use DateTimeInterface;

readonly class CurrencyRateDTO
{
    public function __construct(
        public string $currencyCode,
        public string $baseCurrency,
        public float $rate,
        public string $source,
        public DateTimeInterface $date,
        public ?float $changePercent = null,
        public ?string $trend = null,
        public ?string $currencyName = null,
    ) {}

    public static function fromCbuData(array $data): self
    {
        return new self(
            currencyCode: $data['Ccy'],
            baseCurrency: 'UZS',
            rate: (float) $data['Rate'],
            source: 'cbu',
            date: Carbon::parse($data['Date']),
            currencyName: $data['CcyNm_EN'] ?? null,
        );
    }

    public function format(int $decimals = 2): string
    {
        return number_format($this->rate, $decimals, '.', ' ');
    }

    public function getTrendEmoji(): string
    {
        return match ($this->trend) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️',
        };
    }

    public function withTrend(float $changePercent): self
    {
        $trend = match (true) {
            $changePercent > 0.05 => 'up',
            $changePercent < -0.05 => 'down',
            default => 'stable',
        };

        return new self(
            currencyCode: $this->currencyCode,
            baseCurrency: $this->baseCurrency,
            rate: $this->rate,
            source: $this->source,
            date: $this->date,
            changePercent: $changePercent,
            trend: $trend,
            currencyName: $this->currencyName,
        );
    }
}

