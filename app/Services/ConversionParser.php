<?php

namespace App\Services;

class ConversionParser
{
    private array $currencyAliases = [
        // USD
        'USD' => 'USD', 'DOLLAR' => 'USD', 'DOLLARS' => 'USD',
        'ДОЛЛАР' => 'USD', 'ДОЛЛАРОВ' => 'USD', 'ДОЛЛАРЫ' => 'USD', 'ДОЛЛАРАХ' => 'USD',
        '$' => 'USD',

        // EUR
        'EUR' => 'EUR', 'EURO' => 'EUR', 'EUROS' => 'EUR',
        'ЕВРО' => 'EUR', '€' => 'EUR',

        // RUB
        'RUB' => 'RUB', 'RUBL' => 'RUB', 'RUBLE' => 'RUB', 'RUBLES' => 'RUB',
        'РУБЛЬ' => 'RUB', 'РУБЛЕЙ' => 'RUB', 'РУБЛИ' => 'RUB', 'РУБЛЯХ' => 'RUB',
        '₽' => 'RUB',

        // UZS
        'UZS' => 'UZS', 'SUM' => 'UZS', 'SUMS' => 'UZS', "SO'M" => 'UZS', 'SOM' => 'UZS',
        'СУМ' => 'UZS', 'СУМОВ' => 'UZS', 'СУМЫ' => 'UZS', 'СУМАХ' => 'UZS',

        // GBP
        'GBP' => 'GBP', 'POUND' => 'GBP', 'POUNDS' => 'GBP',
        'ФУНТ' => 'GBP', 'ФУНТОВ' => 'GBP', '£' => 'GBP',

        // CNY
        'CNY' => 'CNY', 'YUAN' => 'CNY', 'RMB' => 'CNY',
        'ЮАНЬ' => 'CNY', 'ЮАНЕЙ' => 'CNY', '¥' => 'CNY',

        // JPY
        'JPY' => 'JPY', 'YEN' => 'JPY', 'ЙЕНА' => 'JPY', 'ЙЕН' => 'JPY',

        // CHF
        'CHF' => 'CHF', 'FRANC' => 'CHF', 'FRANCS' => 'CHF',
        'ФРАНК' => 'CHF', 'ФРАНКОВ' => 'CHF',

        // KZT
        'KZT' => 'KZT', 'TENGE' => 'KZT', 'ТЕНГЕ' => 'KZT',
    ];

    public function parse(string $text): ?array
    {
        $text = trim($text);

        // Try different patterns
        $result = $this->parseStandardFormat($text)
            ?? $this->parseCompactFormat($text)
            ?? $this->parseSimpleFormat($text);

        return $result;
    }

    /**
     * Parse: "100 USD -> UZS", "100 USD to UZS", "100 долларов в сумы"
     */
    private function parseStandardFormat(string $text): ?array
    {
        $pattern = '/^([\d\s,.]+)\s*([a-zA-Zа-яА-ЯёЁ\'$€₽£¥]+)\s*(?:->|→|=>|to|в|на|ga|dan)\s*([a-zA-Zа-яА-ЯёЁ\'$€₽£¥]+)$/iu';

        if (preg_match($pattern, $text, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            $from = $this->normalizeCurrency($matches[2]);
            $to = $this->normalizeCurrency($matches[3]);

            if ($amount > 0 && $from && $to) {
                return compact('amount', 'from', 'to');
            }
        }

        return null;
    }

    /**
     * Parse: "100USD-UZS", "100$-сум"
     */
    private function parseCompactFormat(string $text): ?array
    {
        $pattern = '/^([\d,.]+)\s*([a-zA-Zа-яА-ЯёЁ\'$€₽£¥]+)\s*[-–]\s*([a-zA-Zа-яА-ЯёЁ\'$€₽£¥]+)$/iu';

        if (preg_match($pattern, $text, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            $from = $this->normalizeCurrency($matches[2]);
            $to = $this->normalizeCurrency($matches[3]);

            if ($amount > 0 && $from && $to) {
                return compact('amount', 'from', 'to');
            }
        }

        return null;
    }

    /**
     * Parse: "100 USD" (convert to UZS by default)
     */
    private function parseSimpleFormat(string $text): ?array
    {
        $pattern = '/^([\d\s,.]+)\s*([a-zA-Zа-яА-ЯёЁ\'$€₽£¥]+)$/iu';

        if (preg_match($pattern, $text, $matches)) {
            $amount = $this->parseAmount($matches[1]);
            $from = $this->normalizeCurrency($matches[2]);

            if ($amount > 0 && $from) {
                // Default conversion to UZS, unless it's already UZS
                $to = $from === 'UZS' ? 'USD' : 'UZS';
                return compact('amount', 'from', 'to');
            }
        }

        return null;
    }

    private function parseAmount(string $amount): float
    {
        // Remove spaces, replace comma with dot
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));

        // Handle multiple dots (e.g., 1.000.000 -> 1000000)
        $parts = explode('.', $amount);
        if (count($parts) > 2) {
            $lastPart = array_pop($parts);
            $amount = implode('', $parts) . '.' . $lastPart;
        }

        return (float) $amount;
    }

    private function normalizeCurrency(string $input): ?string
    {
        $input = mb_strtoupper(trim($input));

        return $this->currencyAliases[$input] ?? null;
    }

    public function isConversionRequest(string $text): bool
    {
        return $this->parse($text) !== null;
    }
}

