<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook 
                            {url? : The webhook URL (defaults to config)}
                            {--delete : Delete the current webhook}
                            {--info : Show current webhook info}';

    protected $description = 'Set or manage Telegram bot webhook';

    public function __construct(
        private TelegramService $telegramService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('info')) {
            return $this->showWebhookInfo();
        }

        if ($this->option('delete')) {
            return $this->deleteWebhook();
        }

        return $this->setWebhook();
    }

    private function setWebhook(): int
    {
        $url = $this->argument('url') ?? config('telegram.webhook_url');

        if (!$url) {
            $this->error('Webhook URL is not configured. Set TELEGRAM_WEBHOOK_URL in .env');
            return self::FAILURE;
        }

        $this->info("Setting webhook to: {$url}");

        $secretToken = config('telegram.secret_token');
        $result = $this->telegramService->setWebhook($url, $secretToken);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook set successfully!');

            // Also register commands
            $this->info('Registering bot commands...');
            $this->telegramService->registerCommands();
            $this->info('✅ Commands registered!');

            return self::SUCCESS;
        }

        $this->error('❌ Failed to set webhook: ' . ($result['description'] ?? 'Unknown error'));
        return self::FAILURE;
    }

    private function deleteWebhook(): int
    {
        $this->info('Deleting webhook...');

        $result = $this->telegramService->deleteWebhook(true);

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook deleted successfully!');
            return self::SUCCESS;
        }

        $this->error('❌ Failed to delete webhook: ' . ($result['description'] ?? 'Unknown error'));
        return self::FAILURE;
    }

    private function showWebhookInfo(): int
    {
        $this->info('Fetching webhook info...');

        $result = $this->telegramService->getWebhookInfo();

        if (!($result['ok'] ?? false)) {
            $this->error('❌ Failed to get webhook info');
            return self::FAILURE;
        }

        $info = $result['result'] ?? [];

        $this->table(
            ['Property', 'Value'],
            [
                ['URL', $info['url'] ?: '(not set)'],
                ['Has custom certificate', $info['has_custom_certificate'] ? 'Yes' : 'No'],
                ['Pending update count', $info['pending_update_count'] ?? 0],
                ['Last error date', isset($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : '-'],
                ['Last error message', $info['last_error_message'] ?? '-'],
                ['Max connections', $info['max_connections'] ?? '-'],
                ['Allowed updates', implode(', ', $info['allowed_updates'] ?? [])],
            ]
        );

        return self::SUCCESS;
    }
}

