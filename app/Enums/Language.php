<?php

namespace App\Enums;

enum Language: string
{
    case ENGLISH = 'en';
    case RUSSIAN = 'ru';
    case UZBEK = 'uz';

    public function label(): string
    {
        return match ($this) {
            self::ENGLISH => '🇬🇧 English',
            self::RUSSIAN => '🇷🇺 Русский',
            self::UZBEK => '🇺🇿 O\'zbekcha',
        };
    }

    public function nativeName(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::RUSSIAN => 'Русский',
            self::UZBEK => 'O\'zbekcha',
        };
    }

    public static function fromCode(string $code): self
    {
        return match (strtolower($code)) {
            'en', 'english' => self::ENGLISH,
            'ru', 'russian' => self::RUSSIAN,
            'uz', 'uzbek' => self::UZBEK,
            default => self::ENGLISH,
        };
    }

    public static function all(): array
    {
        return [
            self::ENGLISH,
            self::RUSSIAN,
            self::UZBEK,
        ];
    }
}

