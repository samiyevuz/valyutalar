<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\AlertsKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\AlertService;
use App\Services\TelegramService;

class HandleAlertsAction
{
    public function __construct(
        private AlertService $alertService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $alerts = $this->alertService->getUserAlerts($user);
        $message = $this->alertService->formatAlertsMessage($user);

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            AlertsKeyboard::build($alerts, $user->language)
        );
    }
}

