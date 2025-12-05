<?php

namespace App\DTOs;

use App\Enums\Currency;
use DateTimeInterface;

readonly class ConversionResultDTO
{
    public function __construct(
        public float $amountFrom,
        public string $currencyFrom,
        public float $amountTo,
        public string $currencyTo,
        public float $rate,
        public DateTimeInterface $timestamp,
    ) {}

    public function formatResult(): string
    {
        $formattedFrom = number_format($this->amountFrom, 2, '.', ' ');
        $formattedTo = number_format($this->amountTo, 2, '.', ' ');

        return "{$formattedFrom} {$this->currencyFrom} = {$formattedTo} {$this->currencyTo}";
    }

    public function getFromFlag(): string
    {
        $currency = Currency::fromString($this->currencyFrom);
        return $currency?->flag() ?? '💱';
    }

    public function getToFlag(): string
    {
        $currency = Currency::fromString($this->currencyTo);
        return $currency?->flag() ?? '💱';
    }

    public function formatDetailed(): string
    {
        $fromFormatted = number_format($this->amountFrom, 2, '.', ' ');
        $toFormatted = number_format($this->amountTo, 2, '.', ' ');
        $rateFormatted = number_format($this->rate, 4, '.', ' ');

        return sprintf(
            "%s %s %s\n= %s %s %s\n\n📊 1 %s = %s %s",
            $this->getFromFlag(),
            $fromFormatted,
            $this->currencyFrom,
            $this->getToFlag(),
            $toFormatted,
            $this->currencyTo,
            $this->currencyFrom,
            $rateFormatted,
            $this->currencyTo
        );
    }
}

