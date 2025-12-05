<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramRateLimiter
{
    public function __construct(
        private RateLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->all();

        // Extract user ID from telegram update
        $userId = $data['message']['from']['id']
            ?? $data['callback_query']['from']['id']
            ?? null;

        if (!$userId) {
            return $next($request);
        }

        $key = 'telegram_rate_limit:' . $userId;
        $maxAttempts = config('telegram.rate_limit.max_requests', 30);
        $decayMinutes = config('telegram.rate_limit.per_minutes', 1);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            // Just return OK to avoid Telegram retries
            return response()->json(['ok' => true]);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}

