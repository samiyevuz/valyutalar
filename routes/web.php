<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(['telegram.webhook', 'telegram.rate_limit'])
    ->name('telegram.webhook');

// Test route (for debugging - remove in production)
Route::get('/telegram/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Webhook endpoint is accessible',
        'time' => now()->toDateTimeString(),
    ]);
});
