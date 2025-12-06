<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramBot extends Command
{
    protected $signature = 'telegram:test';
    protected $description = 'Test Telegram bot connection and webhook';

    public function handle(TelegramService $telegramService): int
    {
        $this->info('Testing Telegram Bot...');
        
        // Check token
        $token = config('telegram.bot_token');
        if (empty($token)) {
            $this->error('❌ TELEGRAM_BOT_TOKEN is not set in .env');
            return self::FAILURE;
        }
        
        $this->info('✅ Bot token is configured');
        $this->line('Token: ' . substr($token, 0, 10) . '...');
        
        // Test getMe
        $this->info('Testing getMe API...');
        $me = $telegramService->getMe();
        
        if ($me['ok'] ?? false) {
            $bot = $me['result'] ?? [];
            $this->info('✅ Bot is connected!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['ID', $bot['id'] ?? 'N/A'],
                    ['Username', '@' . ($bot['username'] ?? 'N/A')],
                    ['First Name', $bot['first_name'] ?? 'N/A'],
                    ['Can Join Groups', ($bot['can_join_groups'] ?? false) ? 'Yes' : 'No'],
                    ['Can Read All Group Messages', ($bot['can_read_all_group_messages'] ?? false) ? 'Yes' : 'No'],
                ]
            );
        } else {
            $this->error('❌ Failed to get bot info');
            $this->error('Response: ' . json_encode($me, JSON_PRETTY_PRINT));
            return self::FAILURE;
        }
        
        // Check webhook
        $this->info('Checking webhook status...');
        $webhookInfo = $telegramService->getWebhookInfo();
        
        if ($webhookInfo['ok'] ?? false) {
            $info = $webhookInfo['result'] ?? [];
            $this->table(
                ['Property', 'Value'],
                [
                    ['URL', $info['url'] ?: '(not set)'],
                    ['Pending Updates', $info['pending_update_count'] ?? 0],
                    ['Last Error Date', isset($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : 'None'],
                    ['Last Error Message', $info['last_error_message'] ?? 'None'],
                ]
            );
            
            if (empty($info['url'])) {
                $this->warn('⚠️  Webhook is not set!');
                $this->line('Run: php artisan telegram:set-webhook');
            } else {
                $this->info('✅ Webhook is configured');
            }
        }
        
        return self::SUCCESS;
    }
}

