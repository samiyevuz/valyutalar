<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook - Middleware'ni vaqtincha o'chirib qo'yamiz
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

// Test endpoint
Route::get('/telegram/test', function () {
    $logFile = storage_path('logs/webhook-debug.log');
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] TEST ENDPOINT ACCESSED\n", FILE_APPEND);
    
    return response()->json([
        'status' => 'ok',
        'message' => 'Test endpoint works',
        'log_file' => $logFile,
        'log_exists' => file_exists($logFile),
        'log_writable' => is_writable(dirname($logFile)),
    ]);
});
