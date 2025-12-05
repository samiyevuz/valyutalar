<?php

namespace App\Console\Commands;

use App\Builders\Keyboard\MainMenuKeyboard;
use App\Models\TelegramUser;
use App\Services\CurrencyService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyDigestCommand extends Command
{
    protected $signature = 'telegram:send-digest';

    protected $description = 'Send daily currency digest to subscribed users';

    public function __construct(
        private TelegramService $telegramService,
        private CurrencyService $currencyService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Sending daily digest...');

        $users = TelegramUser::active()
            ->withDigestEnabled()
            ->get();

        $this->info("Found {$users->count()} subscribed users");

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendDigestToUser($user);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to send digest', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("✅ Done! Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }

    private function sendDigestToUser(TelegramUser $user): void
    {
        app()->setLocale($user->language);

        $favorites = $user->getFavoriteCurrencies();
        $message = $this->buildDigestMessage($favorites, $user->language);

        $this->telegramService->sendMessage(
            $user->telegram_id,
            $message,
            MainMenuKeyboard::buildCompact($user->language)
        );
    }

    private function buildDigestMessage(array $currencies, string $lang): string
    {
        $lines = [
            '🌅 <b>' . __('bot.digest.title', locale: $lang) . '</b>',
            '',
            '📅 ' . now()->format('d.m.Y'),
            '',
        ];

        foreach ($currencies as $currency) {
            $rate = $this->currencyService->getRate($currency);

            if (!$rate) {
                continue;
            }

            $trend = $this->currencyService->getTrend($currency, 1);
            $emoji = match ($trend['trend']) {
                'up' => '📈',
                'down' => '📉',
                default => '➡️',
            };

            $change = '';
            if ($trend['change_percent'] != 0) {
                $change = sprintf(' (%+.2f%%)', $trend['change_percent']);
            }

            $lines[] = sprintf(
                '%s <b>%s</b>: %s UZS%s',
                $emoji,
                $currency,
                number_format($rate->rate, 2, '.', ' '),
                $change
            );
        }

        $lines[] = '';
        $lines[] = '<i>' . __('bot.digest.footer', locale: $lang) . '</i>';

        return implode("\n", $lines);
    }
}

