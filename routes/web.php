<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(['telegram.webhook', 'telegram.rate_limit'])
    ->name('telegram.webhook');

// Test route (for debugging - remove in production)
Route::get('/telegram/test', function () {
    Log::info('Test endpoint accessed', [
        'time' => now()->toDateTimeString(),
        'ip' => request()->ip(),
    ]);

    return response()->json([
        'status' => 'ok',
        'message' => 'Webhook endpoint is accessible',
        'time' => now()->toDateTimeString(),
        'log_written' => true,
    ]);
});

// Manual webhook test (for debugging)
Route::post('/telegram/test-webhook', function () {
    $data = request()->all();
    
    Log::info('Manual webhook test', [
        'data' => $data,
        'ip' => request()->ip(),
    ]);

    return response()->json([
        'status' => 'ok',
        'received_data' => $data,
        'log_written' => true,
    ]);
});
