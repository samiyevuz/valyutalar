<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $chatId,
        private string $message,
        private ?array $replyMarkup = null,
        private int $tries = 3,
        private int $timeout = 30,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        try {
            $telegramService->sendMessage(
                $this->chatId,
                $this->message,
                $this->replyMarkup
            );
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendTelegramMessageJob failed', [
            'chat_id' => $this->chatId,
            'error' => $exception->getMessage(),
        ]);
    }
}

