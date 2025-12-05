<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case RUB = 'RUB';
    case UZS = 'UZS';
    case GBP = 'GBP';
    case CNY = 'CNY';
    case JPY = 'JPY';
    case CHF = 'CHF';
    case KZT = 'KZT';

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::RUB => '₽',
            self::UZS => 'сум',
            self::GBP => '£',
            self::CNY => '¥',
            self::JPY => '¥',
            self::CHF => '₣',
            self::KZT => '₸',
        };
    }

    public function flag(): string
    {
        return match ($this) {
            self::USD => '🇺🇸',
            self::EUR => '🇪🇺',
            self::RUB => '🇷🇺',
            self::UZS => '🇺🇿',
            self::GBP => '🇬🇧',
            self::CNY => '🇨🇳',
            self::JPY => '🇯🇵',
            self::CHF => '🇨🇭',
            self::KZT => '🇰🇿',
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::RUB => 'Russian Ruble',
            self::UZS => 'Uzbek Sum',
            self::GBP => 'British Pound',
            self::CNY => 'Chinese Yuan',
            self::JPY => 'Japanese Yen',
            self::CHF => 'Swiss Franc',
            self::KZT => 'Kazakh Tenge',
        };
    }

    public static function main(): array
    {
        return [
            self::USD,
            self::EUR,
            self::RUB,
            self::GBP,
        ];
    }

    public static function fromString(string $code): ?self
    {
        $code = strtoupper(trim($code));

        foreach (self::cases() as $currency) {
            if ($currency->value === $code) {
                return $currency;
            }
        }

        return null;
    }
}

