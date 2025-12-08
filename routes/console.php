<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file defines the console commands and scheduled tasks for the bot.
|
*/

// Check price alerts every 30 minutes
Schedule::command('telegram:check-alerts')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Send daily digest at 9:00 AM Tashkent time
Schedule::command('telegram:send-digest')
    ->dailyAt('09:00')
    ->timezone('Asia/Tashkent')
    ->withoutOverlapping();

// Fetch currency rates every 5 hours
Schedule::command('telegram:fetch-rates')
    ->cron('0 */5 * * *')
    ->withoutOverlapping()
    ->runInBackground();

// Fetch bank rates every 5 hours (same as currency rates)
Schedule::command('telegram:fetch-bank-rates')
    ->cron('0 */5 * * *')
    ->withoutOverlapping()
    ->runInBackground();
