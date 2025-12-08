<?php

namespace App\Services;

use App\Builders\Keyboard\MainMenuKeyboard;
use App\Models\Alert;
use App\Models\TelegramUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AlertService
{
    public function __construct(
        private CurrencyService $currencyService,
        private TelegramService $telegramService,
    ) {}

    public function createAlert(
        TelegramUser $user,
        string $currencyFrom,
        string $currencyTo,
        string $condition,
        float $targetRate
    ): Alert {
        return Alert::create([
            'telegram_user_id' => $user->id,
            'currency_from' => strtoupper($currencyFrom),
            'currency_to' => strtoupper($currencyTo),
            'condition' => $condition,
            'target_rate' => $targetRate,
        ]);
    }

    public function parseAndCreateAlert(string $text, TelegramUser $user): ?Alert
    {
        // Patterns:
        // "USD < 12500"
        // "USD > 12500 UZS"
        // "Notify me when EUR < 14000"
        // "USD 12500 dan past"
        // "USD 12500 dan yuqori"

        $patterns = [
            // Standard: "USD < 12500" or "USD > 12500 UZS"
            '/(\w{3})\s*([<>]|>=|<=)\s*([\d\s,.]+)\s*(\w{3})?/iu',

            // Russian: "USD ниже 12500" or "USD выше 12500"
            '/(\w{3})\s*(ниже|выше|меньше|больше)\s*([\d\s,.]+)\s*(\w{3})?/iu',

            // Uzbek: "USD 12500 dan past" or "USD 12500 dan yuqori"
            '/(\w{3})\s*([\d\s,.]+)\s*(?:dan|дан)?\s*(past|yuqori|baland|kam)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->createAlertFromMatches($matches, $user);
            }
        }

        return null;
    }

    private function createAlertFromMatches(array $matches, TelegramUser $user): ?Alert
    {
        $currencyFrom = strtoupper($matches[1]);
        $condition = $this->normalizeCondition($matches[2]);
        $targetRate = $this->parseAmount($matches[3] ?? $matches[2]);
        $currencyTo = isset($matches[4]) ? strtoupper($matches[4]) : 'UZS';

        // Swap if needed (for Uzbek format where amount comes before condition)
        if (!in_array($condition, ['above', 'below'])) {
            $condition = $this->normalizeCondition($matches[3] ?? '');
            $targetRate = $this->parseAmount($matches[2]);
        }

        if (!$condition || $targetRate <= 0) {
            return null;
        }

        return $this->createAlert($user, $currencyFrom, $currencyTo, $condition, $targetRate);
    }

    private function normalizeCondition(string $input): ?string
    {
        $input = mb_strtolower(trim($input));

        $aboveKeywords = ['>', '>=', 'выше', 'больше', 'yuqori', 'baland', 'above', 'greater'];
        $belowKeywords = ['<', '<=', 'ниже', 'меньше', 'past', 'kam', 'below', 'less'];

        if (in_array($input, $aboveKeywords)) {
            return 'above';
        }

        if (in_array($input, $belowKeywords)) {
            return 'below';
        }

        return null;
    }

    private function parseAmount(string $amount): float
    {
        $amount = str_replace([' ', ','], ['', '.'], trim($amount));
        return (float) $amount;
    }

    public function getUserAlerts(TelegramUser $user, bool $activeOnly = true): Collection
    {
        $query = $user->alerts();

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function deleteAlert(int $alertId, TelegramUser $user): bool
    {
        return $user->alerts()->where('id', $alertId)->delete() > 0;
    }

    public function checkAllAlerts(): int
    {
        $triggered = 0;
        $alerts = Alert::active()->with('telegramUser')->get();

        foreach ($alerts as $alert) {
            try {
                $currentRate = $this->currencyService->getConversionRate(
                    $alert->currency_from,
                    $alert->currency_to
                );

                if ($currentRate > 0 && $alert->checkCondition($currentRate)) {
                    $this->triggerAlert($alert, $currentRate);
                    $triggered++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to check alert', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $triggered;
    }

    private function triggerAlert(Alert $alert, float $currentRate): void
    {
        $alert->trigger($currentRate);

        $user = $alert->telegramUser;

        if (!$user || $user->is_blocked) {
            return;
        }

        app()->setLocale($user->language);

        $conditionSymbol = $alert->getConditionSymbol();
        $message = "🔔 <b>" . __('bot.alerts.triggered_title') . "</b>\n\n";
        $message .= sprintf(
            "%s/%s %s %s\n\n",
            $alert->currency_from,
            $alert->currency_to,
            $conditionSymbol,
            number_format((float) $alert->target_rate, 2, '.', ' ')
        );
        $message .= __('bot.alerts.current_rate') . ': <b>' . number_format($currentRate, 2, '.', ' ') . "</b>\n\n";
        $message .= '<i>' . __('bot.alerts.triggered_note') . '</i>';

        $this->telegramService->sendMessage(
            $user->telegram_id,
            $message,
            \App\Builders\Keyboard\MainMenuKeyboard::buildCompact($user->language)
        );
    }

    public function formatAlertsMessage(TelegramUser $user): string
    {
        $alerts = $this->getUserAlerts($user);

        if ($alerts->isEmpty()) {
            return __('bot.alerts.no_alerts', locale: $user->language);
        }

        $lines = ['🔔 <b>' . __('bot.alerts.your_alerts', locale: $user->language) . '</b>'];
        $lines[] = '';

        foreach ($alerts as $index => $alert) {
            $status = $alert->getStatusEmoji();
            $lines[] = sprintf(
                '%d. %s %s',
                $index + 1,
                $status,
                $alert->getDescription()
            );
        }

        $lines[] = '';
        $instructions = __('bot.alerts.instructions', locale: $user->language);
        $lines[] = '<i>' . $instructions . '</i>';

        $message = implode("\n", $lines);
        
        // Telegram message limit is 4096 characters
        if (strlen($message) > 4096) {
            // Truncate if too long, but keep HTML tags balanced
            $message = substr($message, 0, 4090);
            // Remove any incomplete HTML tags at the end
            $message = preg_replace('/<[^>]*$/', '', $message);
            // Ensure closing tags
            if (strpos($message, '<i>') !== false && strpos($message, '</i>') === false) {
                $message .= '</i>';
            }
            if (strpos($message, '<b>') !== false && strpos($message, '</b>') === false) {
                $message .= '</b>';
            }
        }

        return $message;
    }
}

