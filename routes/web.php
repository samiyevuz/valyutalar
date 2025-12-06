<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook - NO MIDDLEWARE for debugging
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Test endpoint
Route::get('/telegram/test', function () {
    $logFile = storage_path('logs/webhook-debug.log');
    $logDir = dirname($logFile);
    
    // Ensure directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Write test log
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TEST ENDPOINT ACCESSED\n", FILE_APPEND);
    
    return response()->json([
        'status' => 'ok',
        'message' => 'Test endpoint works',
        'log_file' => $logFile,
        'log_exists' => file_exists($logFile),
        'log_writable' => is_writable($logDir),
        'log_dir' => $logDir,
        'time' => now()->toDateTimeString(),
    ]);
});

// Webhook test endpoint (POST)
Route::post('/telegram/test-webhook', function () {
    $logFile = storage_path('logs/webhook-debug.log');
    $logDir = dirname($logFile);
    
    // Ensure directory exists
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $data = request()->all();
    
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TEST WEBHOOK POST: " . json_encode($data) . "\n", FILE_APPEND);
    
    return response()->json([
        'status' => 'ok',
        'received_data' => $data,
        'log_written' => true,
        'time' => now()->toDateTimeString(),
    ]);
});
