<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'telegram.webhook' => \App\Http\Middleware\ValidateTelegramWebhook::class,
            'telegram.rate_limit' => \App\Http\Middleware\TelegramRateLimiter::class,
        ]);
        
        // Exclude Telegram webhook from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
