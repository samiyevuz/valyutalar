<?php

namespace App\DTOs;

use Illuminate\Support\Arr;

readonly class TelegramMessageDTO
{
    public function __construct(
        public int $messageId,
        public int $chatId,
        public TelegramUserDTO $from,
        public ?string $text,
        public int $date,
        public ?array $entities,
        public ?string $chatType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            messageId: Arr::get($data, 'message_id', 0),
            chatId: Arr::get($data, 'chat.id', 0),
            from: TelegramUserDTO::fromArray(Arr::get($data, 'from', [])),
            text: Arr::get($data, 'text'),
            date: Arr::get($data, 'date', 0),
            entities: Arr::get($data, 'entities'),
            chatType: Arr::get($data, 'chat.type'),
        );
    }

    public function isCommand(): bool
    {
        if (!$this->text) {
            return false;
        }

        // Check if text starts with / (command prefix)
        if (str_starts_with(trim($this->text), '/')) {
            return true;
        }

        // Also check entities if available
        if ($this->entities) {
            foreach ($this->entities as $entity) {
                if (($entity['type'] ?? null) === 'bot_command' && ($entity['offset'] ?? -1) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getCommand(): ?string
    {
        if (!$this->isCommand()) {
            return null;
        }

        $parts = explode(' ', $this->text);
        $command = $parts[0];

        // Remove bot username if present (@BotName)
        if (str_contains($command, '@')) {
            $command = explode('@', $command)[0];
        }

        return ltrim($command, '/');
    }

    public function getCommandArgs(): string
    {
        if (!$this->isCommand()) {
            return '';
        }

        $parts = explode(' ', $this->text, 2);
        return trim($parts[1] ?? '');
    }

    public function isPrivateChat(): bool
    {
        return $this->chatType === 'private';
    }

    public function isGroupChat(): bool
    {
        return in_array($this->chatType, ['group', 'supergroup']);
    }
}

