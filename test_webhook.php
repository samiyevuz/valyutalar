<?php

/**
 * Test script to check webhook functionality
 * Run: php test_webhook.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Telegram Webhook ===\n\n";

// Test 1: Check .env file
echo "1. Checking .env file...\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "   ✓ .env file exists\n";
    $envContent = file_get_contents($envFile);
    if (strpos($envContent, 'TELEGRAM_BOT_TOKEN') !== false) {
        echo "   ✓ TELEGRAM_BOT_TOKEN found in .env\n";
    } else {
        echo "   ✗ TELEGRAM_BOT_TOKEN NOT found in .env\n";
    }
} else {
    echo "   ✗ .env file NOT found\n";
}

// Test 2: Check config
echo "\n2. Checking config...\n";
try {
    $token = config('telegram.bot_token');
    if ($token) {
        echo "   ✓ Bot token is set (length: " . strlen($token) . ")\n";
    } else {
        echo "   ✗ Bot token is NOT set\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error reading config: " . $e->getMessage() . "\n";
}

// Test 3: Check database connection
echo "\n3. Checking database connection...\n";
try {
    \DB::connection()->getPdo();
    echo "   ✓ Database connection successful\n";
} catch (\Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 4: Check log directory
echo "\n4. Checking log directory...\n";
$logDir = __DIR__ . '/storage/logs';
if (is_dir($logDir)) {
    echo "   ✓ Log directory exists\n";
    if (is_writable($logDir)) {
        echo "   ✓ Log directory is writable\n";
    } else {
        echo "   ✗ Log directory is NOT writable\n";
    }
} else {
    echo "   ✗ Log directory NOT found\n";
}

// Test 5: Check webhook route
echo "\n5. Checking webhook route...\n";
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
        echo "   ✓ Webhook route found\n";
        echo "   ✓ Route method: " . implode(', ', $webhookRoute->methods()) . "\n";
    } else {
        echo "   ✗ Webhook route NOT found\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking routes: " . $e->getMessage() . "\n";
}

// Test 6: Test TelegramService
echo "\n6. Testing TelegramService...\n";
try {
    $service = app(\App\Services\TelegramService::class);
    echo "   ✓ TelegramService created successfully\n";
} catch (\Exception $e) {
    echo "   ✗ TelegramService creation failed: " . $e->getMessage() . "\n";
}

// Test 7: Check log file
echo "\n7. Checking log file...\n";
$logFile = __DIR__ . '/storage/logs/webhook-debug.log';
if (file_exists($logFile)) {
    echo "   ✓ Log file exists\n";
    $logSize = filesize($logFile);
    echo "   ✓ Log file size: " . $logSize . " bytes\n";
    if ($logSize > 0) {
        $lastLines = array_slice(file($logFile), -5);
        echo "   Last 5 lines:\n";
        foreach ($lastLines as $line) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   ✗ Log file NOT found\n";
}

echo "\n=== Test Complete ===\n";

