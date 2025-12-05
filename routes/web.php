<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook (uses web routes for simpler middleware)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(['telegram.webhook', 'telegram.rate_limit'])
    ->name('telegram.webhook');
