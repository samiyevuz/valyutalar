<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestWebhookConnection extends Command
{
    protected $signature = 'webhook:test-connection';
    protected $description = 'Test webhook connection by sending a test request';

    public function handle(TelegramService $telegramService): int
    {
        $this->info('Testing webhook connection...');
        $this->newLine();

        // 1. Check webhook URL
        $webhookUrl = config('telegram.webhook_url');
        if (empty($webhookUrl)) {
            $this->error('❌ TELEGRAM_WEBHOOK_URL is not set in .env');
            return self::FAILURE;
        }
        $this->info('✅ Webhook URL: ' . $webhookUrl);
        $this->newLine();

        // 2. Create log directory and file
        $logFile = storage_path('logs/webhook-debug.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
            $this->info('✅ Created log directory: ' . $logDir);
        }
        
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0664);
            $this->info('✅ Created log file: ' . $logFile);
        }
        
        $this->info('✅ Log file exists and is writable');
        $this->newLine();

        // 3. Test webhook endpoint with a test request
        $this->info('3. Sending test request to webhook...');
        
        $testUpdate = [
            'update_id' => 999999999,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 999999999,
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => 999999999,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
                'entities' => [
                    [
                        'offset' => 0,
                        'length' => 6,
                        'type' => 'bot_command',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->post($webhookUrl, $testUpdate);

            $this->info('✅ Request sent successfully');
            $this->info('   Status: ' . $response->status());
            $this->info('   Response: ' . $response->body());
            
            if ($response->successful()) {
                $this->info('✅ Webhook responded successfully');
            } else {
                $this->error('❌ Webhook returned error status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to send request: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();

        // 4. Check log file
        $this->info('4. Checking log file...');
        sleep(2); // Wait for log to be written
        
        if (file_exists($logFile)) {
            $logSize = filesize($logFile);
            $this->info('✅ Log file exists (size: ' . $logSize . ' bytes)');
            
            if ($logSize > 0) {
                $lines = file($logFile);
                $lastLines = array_slice($lines, -10);
                $this->info('   Last 10 lines:');
                foreach ($lastLines as $line) {
                    $this->line('   ' . trim($line));
                }
            } else {
                $this->warn('⚠️  Log file is empty');
            }
        } else {
            $this->error('❌ Log file does not exist');
        }

        $this->newLine();
        $this->info('=== Test Complete ===');
        return self::SUCCESS;
    }
}



