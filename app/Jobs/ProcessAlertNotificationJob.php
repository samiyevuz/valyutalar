<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Services\AlertService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlertNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $alertId,
        private int $tries = 3,
        private int $timeout = 30,
    ) {}

    public function handle(AlertService $alertService, TelegramService $telegramService): void
    {
        try {
            $alert = Alert::find($this->alertId);

            if (!$alert || !$alert->is_active || $alert->is_triggered) {
                return;
            }

            $user = $alert->telegramUser;

            if ($user->is_blocked) {
                return;
            }

            app()->setLocale($user->language ?? 'en');

            $message = __('bot.alerts.triggered', [
                'currency_from' => $alert->currency_from,
                'currency_to' => $alert->currency_to,
                'condition' => $alert->condition === 'above' ? '>' : '<',
                'target_rate' => number_format($alert->target_rate, 2),
                'current_rate' => number_format($alert->triggered_rate ?? 0, 2),
            ]);

            $telegramService->sendMessage(
                $user->telegram_id,
                "🔔 {$message}"
            );
        } catch (\Exception $e) {
            Log::error('Failed to process alert notification', [
                'alert_id' => $this->alertId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAlertNotificationJob failed', [
            'alert_id' => $this->alertId,
            'error' => $exception->getMessage(),
        ]);
    }
}

