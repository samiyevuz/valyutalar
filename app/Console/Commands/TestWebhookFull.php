<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TestWebhookFull extends Command
{
    protected $signature = 'webhook:test-full';
    protected $description = 'Full test of webhook functionality';

    public function handle(TelegramService $telegramService): int
    {
        $this->info('=== Full Webhook Test ===');
        $this->newLine();

        // Test 1: .env file
        $this->info('1. Checking .env file...');
        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $this->info('   ✓ .env file exists');
            $envContent = File::get($envFile);
            if (strpos($envContent, 'TELEGRAM_BOT_TOKEN') !== false) {
                $this->info('   ✓ TELEGRAM_BOT_TOKEN found in .env');
            } else {
                $this->error('   ✗ TELEGRAM_BOT_TOKEN NOT found in .env');
            }
        } else {
            $this->error('   ✗ .env file NOT found');
        }
        $this->newLine();

        // Test 2: Config
        $this->info('2. Checking config...');
        try {
            $token = config('telegram.bot_token');
            if ($token) {
                $this->info('   ✓ Bot token is set (length: ' . strlen($token) . ')');
            } else {
                $this->error('   ✗ Bot token is NOT set');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Error reading config: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 3: Database
        $this->info('3. Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->info('   ✓ Database connection successful');
            $result = DB::select('SELECT DATABASE() as db');
            $this->info('   ✓ Current database: ' . ($result[0]->db ?? 'unknown'));
        } catch (\Exception $e) {
            $this->error('   ✗ Database connection failed: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 4: Log directory
        $this->info('4. Checking log directory...');
        $logDir = storage_path('logs');
        if (is_dir($logDir)) {
            $this->info('   ✓ Log directory exists');
            if (is_writable($logDir)) {
                $this->info('   ✓ Log directory is writable');
            } else {
                $this->error('   ✗ Log directory is NOT writable');
            }
        } else {
            $this->error('   ✗ Log directory NOT found');
        }
        $this->newLine();

        // Test 5: Webhook route
        $this->info('5. Checking webhook route...');
        try {
            $routes = \Route::getRoutes();
            $webhookRoute = null;
            foreach ($routes as $route) {
                if ($route->uri() === 'telegram/webhook') {
                    $webhookRoute = $route;
                    break;
                }
            }
            if ($webhookRoute) {
                $this->info('   ✓ Webhook route found');
                $this->info('   ✓ Route method: ' . implode(', ', $webhookRoute->methods()));
            } else {
                $this->error('   ✗ Webhook route NOT found');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Error checking routes: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 6: TelegramService
        $this->info('6. Testing TelegramService...');
        try {
            $service = app(TelegramService::class);
            $this->info('   ✓ TelegramService created successfully');
            
            // Test getMe
            $me = $service->getMe();
            if ($me['ok'] ?? false) {
                $bot = $me['result'] ?? [];
                $this->info('   ✓ Bot is connected!');
                $this->info('   ✓ Bot ID: ' . ($bot['id'] ?? 'N/A'));
                $this->info('   ✓ Bot Username: @' . ($bot['username'] ?? 'N/A'));
            } else {
                $this->error('   ✗ Failed to get bot info');
                $this->error('   Response: ' . json_encode($me));
            }
        } catch (\Exception $e) {
            $this->error('   ✗ TelegramService error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 7: Log file
        $this->info('7. Checking log file...');
        $logFile = storage_path('logs/webhook-debug.log');
        if (File::exists($logFile)) {
            $this->info('   ✓ Log file exists');
            $logSize = File::size($logFile);
            $this->info('   ✓ Log file size: ' . $logSize . ' bytes');
            if ($logSize > 0) {
                $lines = File::lines($logFile);
                $lastLines = $lines->take(-5);
                $this->info('   Last 5 lines:');
                foreach ($lastLines as $line) {
                    $this->line('   ' . trim($line));
                }
            }
        } else {
            $this->warn('   ⚠ Log file NOT found (this is OK if webhook has not been called yet)');
        }
        $this->newLine();

        // Test 8: Webhook info
        $this->info('8. Checking webhook info...');
        try {
            $webhookInfo = $telegramService->getWebhookInfo();
            if ($webhookInfo['ok'] ?? false) {
                $info = $webhookInfo['result'] ?? [];
                $this->info('   ✓ Webhook URL: ' . ($info['url'] ?: '(not set)'));
                $this->info('   ✓ Pending Updates: ' . ($info['pending_update_count'] ?? 0));
                if (isset($info['last_error_message'])) {
                    $this->error('   ✗ Last Error: ' . $info['last_error_message']);
                }
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Error getting webhook info: ' . $e->getMessage());
        }
        $this->newLine();

        $this->info('=== Test Complete ===');
        return self::SUCCESS;
    }
}

