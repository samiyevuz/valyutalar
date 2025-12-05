<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelegramWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validate secret token if configured
        $secretToken = config('telegram.secret_token');

        if ($secretToken) {
            $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

            if ($headerToken !== $secretToken) {
                abort(401, 'Invalid secret token');
            }
        }

        // Validate IP address (optional, can be disabled in production behind proxy)
        if (config('app.env') === 'production' && config('telegram.validate_ip', false)) {
            if (!$this->isValidTelegramIp($request->ip())) {
                abort(403, 'Invalid IP address');
            }
        }

        return $next($request);
    }

    private function isValidTelegramIp(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        $allowedRanges = config('telegram.allowed_ips', []);

        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $mask);

        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}

