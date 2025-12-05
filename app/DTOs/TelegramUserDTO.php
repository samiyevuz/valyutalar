<?php

namespace App\DTOs;

use Illuminate\Support\Arr;

readonly class TelegramUserDTO
{
    public function __construct(
        public int $id,
        public ?string $username,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $languageCode,
        public bool $isBot,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'id', 0),
            username: Arr::get($data, 'username'),
            firstName: Arr::get($data, 'first_name'),
            lastName: Arr::get($data, 'last_name'),
            languageCode: Arr::get($data, 'language_code'),
            isBot: Arr::get($data, 'is_bot', false),
        );
    }

    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function getDisplayName(): string
    {
        if ($this->firstName) {
            return $this->firstName;
        }

        if ($this->username) {
            return '@' . $this->username;
        }

        return 'User';
    }
}

