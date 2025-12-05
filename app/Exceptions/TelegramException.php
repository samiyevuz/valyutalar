<?php

namespace App\Exceptions;

use Exception;

class TelegramException extends Exception
{
    public static function invalidToken(): self
    {
        return new self('Invalid Telegram bot token');
    }

    public static function apiError(string $message): self
    {
        return new self("Telegram API error: {$message}");
    }

    public static function webhookError(string $message): self
    {
        return new self("Webhook error: {$message}");
    }
}

