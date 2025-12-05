<?php

namespace App\Exceptions;

use Exception;

class CurrencyApiException extends Exception
{
    public static function apiUnavailable(): self
    {
        return new self('Currency API is temporarily unavailable');
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: {$currency}");
    }

    public static function conversionFailed(string $from, string $to): self
    {
        return new self("Failed to convert from {$from} to {$to}");
    }
}

