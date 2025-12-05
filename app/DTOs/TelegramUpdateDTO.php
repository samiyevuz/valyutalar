<?php

namespace App\DTOs;

use Illuminate\Support\Arr;

readonly class TelegramUpdateDTO
{
    public function __construct(
        public int $updateId,
        public ?TelegramMessageDTO $message,
        public ?array $callbackQuery,
        public ?array $rawData,
    ) {}

    public static function fromArray(array $data): self
    {
        $message = Arr::get($data, 'message') ?? Arr::get($data, 'edited_message');
        $callbackQuery = Arr::get($data, 'callback_query');

        return new self(
            updateId: Arr::get($data, 'update_id', 0),
            message: $message ? TelegramMessageDTO::fromArray($message) : null,
            callbackQuery: $callbackQuery,
            rawData: $data,
        );
    }

    public function isCommand(): bool
    {
        return $this->message?->isCommand() ?? false;
    }

    public function getCommand(): ?string
    {
        return $this->message?->getCommand();
    }

    public function getCommandArgs(): string
    {
        return $this->message?->getCommandArgs() ?? '';
    }

    public function isCallbackQuery(): bool
    {
        return $this->callbackQuery !== null;
    }

    public function getCallbackData(): ?string
    {
        return Arr::get($this->callbackQuery, 'data');
    }

    public function getCallbackQueryId(): ?string
    {
        return Arr::get($this->callbackQuery, 'id');
    }

    public function getCallbackMessageId(): ?int
    {
        return Arr::get($this->callbackQuery, 'message.message_id');
    }

    public function getChatId(): int
    {
        if ($this->isCallbackQuery()) {
            return (int) Arr::get($this->callbackQuery, 'message.chat.id', 0);
        }

        return $this->message?->chatId ?? 0;
    }

    public function getUser(): TelegramUserDTO
    {
        if ($this->isCallbackQuery()) {
            return TelegramUserDTO::fromArray(Arr::get($this->callbackQuery, 'from', []));
        }

        return $this->message?->from ?? TelegramUserDTO::fromArray([]);
    }

    public function getText(): ?string
    {
        return $this->message?->text;
    }

    public function hasText(): bool
    {
        return !empty($this->message?->text);
    }
}

