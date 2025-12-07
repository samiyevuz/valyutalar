<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\CurrencyKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\ChartService;
use App\Services\CurrencyService;
use App\Services\TelegramService;

class HandleHistoryAction
{
    public function __construct(
        private CurrencyService $currencyService,
        private ChartService $chartService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $args = $update->getCommandArgs();

        if ($args) {
            $parts = explode(' ', trim($args));
            $currency = strtoupper($parts[0]);
            $days = isset($parts[1]) ? (int) $parts[1] : 30;

            $this->showHistory($update->getChatId(), $currency, $days, $user, $telegram);
            return;
        }

        // Show currency selection
        $telegram->sendMessage(
            $update->getChatId(),
            '📊 ' . __('bot.history.select_currency', locale: $user->language),
            CurrencyKeyboard::buildForHistory($user->language)
        );
    }

    public function showHistory(
        int $chatId,
        string $currency,
        int $days,
        TelegramUser $user,
        TelegramService $telegram,
        ?int $messageId = null
    ): void {
        \Log::info('HandleHistoryAction::showHistory', [
            'chat_id' => $chatId,
            'currency' => $currency,
            'days' => $days,
            'message_id' => $messageId,
        ]);

        // Send typing indicator
        $telegram->sendChatAction($chatId, 'typing');

        try {
            // Clear cache to force fresh data fetch
            $cacheKey = "currency_history_{$currency}_{$days}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            \Log::info('Fetching historical rates', [
                'currency' => $currency,
                'days' => $days,
                'chat_id' => $chatId,
            ]);
            
            $rates = $this->currencyService->getHistoricalRates($currency, $days);
            \Log::info('Historical rates fetched', [
                'currency' => $currency,
                'days' => $days,
                'count' => $rates->count(),
            ]);

            // If no data, try multiple fallback strategies
            if ($rates->isEmpty()) {
                \Log::warning('No historical data found, trying fallback strategies', [
                    'currency' => $currency,
                    'days' => $days,
                ]);
                
                // Strategy 1: Try to get current rate
                $currentRate = $this->currencyService->getRate($currency);
                if ($currentRate) {
                    $rates = collect([$currentRate]);
                    \Log::info('Using current rate as fallback', [
                        'currency' => $currency,
                        'rate' => $currentRate->rate,
                    ]);
                } else {
                    // Strategy 2: Try to get from database with longer period
                    try {
                        $dbRates = \App\Models\CurrencyRate::getHistoricalRates($currency, min($days * 2, 365));
                        if ($dbRates->isNotEmpty()) {
                            $rates = $dbRates->map(fn($r) => new \App\DTOs\CurrencyRateDTO(
                                currencyCode: $r->currency_code,
                                baseCurrency: $r->base_currency,
                                rate: (float) $r->rate,
                                source: $r->source,
                                date: $r->rate_date,
                            ));
                            \Log::info('Using extended DB rates as fallback', [
                                'currency' => $currency,
                                'count' => $rates->count(),
                            ]);
                        }
                    } catch (\Exception $dbError) {
                        \Log::error('Error getting extended DB rates', [
                            'error' => $dbError->getMessage(),
                        ]);
                    }
                }
            }

            if ($rates->isEmpty()) {
                \Log::error('No historical data found after all fallback strategies', [
                    'currency' => $currency,
                    'days' => $days,
                ]);
                $errorMessage = '❌ ' . __('bot.history.no_data', locale: $user->language);
                // Add main menu button to keyboard
                $keyboard = CurrencyKeyboard::buildForHistory($user->language);
                if ($messageId) {
                    try {
                        $telegram->editMessageText(
                            $chatId,
                            $messageId,
                            $errorMessage,
                            $keyboard
                        );
                    } catch (\Exception $e) {
                        $telegram->sendMessage($chatId, $errorMessage, $keyboard);
                    }
                } else {
                    $telegram->sendMessage($chatId, $errorMessage, $keyboard);
                }
                return;
            }

            $trend = $this->currencyService->getTrend($currency, min($days, max(1, $rates->count())));
            \Log::info('Trend calculated', [
                'currency' => $currency,
                'trend' => $trend,
                'rates_count' => $rates->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching historical rates', [
                'currency' => $currency,
                'days' => $days,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $errorMessage = '❌ ' . __('bot.errors.api_error', locale: $user->language);
            if ($messageId) {
                try {
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildForHistory($user->language);
                    $telegram->editMessageText(
                        $chatId,
                        $messageId,
                        $errorMessage,
                        $keyboard
                    );
                } catch (\Exception $editError) {
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildForHistory($user->language);
                    $telegram->sendMessage($chatId, $errorMessage, $keyboard);
                }
            } else {
                // Add main menu button to keyboard
                $keyboard = CurrencyKeyboard::buildForHistory($user->language);
                $telegram->sendMessage($chatId, $errorMessage, $keyboard);
            }
            return;
        }

        // Build caption
        $caption = $this->buildCaption($currency, $days, $trend, $user->language);

        // Generate chart URL only if we have enough data points
        $chartUrl = null;
        if ($rates->count() >= 2) {
            $chartUrl = $this->chartService->generateRateChart($rates, $currency, $days);
            \Log::info('Chart URL generated', [
                'currency' => $currency,
                'has_url' => !empty($chartUrl),
                'rates_count' => $rates->count(),
            ]);
        }

        // Send chart as photo if URL is available
        if ($chartUrl) {
            try {
                // For photos, we can't edit, so delete old message if exists and send new
                if ($messageId) {
                    try {
                        $telegram->deleteMessage($chatId, $messageId);
                    } catch (\Exception $e) {
                        // Ignore if delete fails
                        \Log::debug('Failed to delete old message', ['error' => $e->getMessage()]);
                    }
                }
                // Add main menu button to keyboard
                $keyboard = CurrencyKeyboard::buildPeriodSelector($currency, $user->language);
                
                \Log::info('Sending chart photo', [
                    'currency' => $currency,
                    'url_length' => strlen($chartUrl),
                ]);
                
                $result = $telegram->sendPhoto(
                    $chatId,
                    $chartUrl,
                    $caption,
                    $keyboard
                );
                
                \Log::info('Chart photo sent', [
                    'currency' => $currency,
                    'ok' => $result['ok'] ?? false,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send chart photo, falling back to text chart', [
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                ]);
                // Fallback to text chart
                $chartUrl = null;
            }
        }

        // Fallback to text chart if photo failed or not enough data
        if (!$chartUrl) {
            \Log::info('Using text chart', [
                'currency' => $currency,
                'rates_count' => $rates->count(),
            ]);
            
            $textChart = $this->chartService->generateTextChart($rates);
            $fullMessage = $caption . "\n\n" . $textChart;
            
            if ($messageId) {
                try {
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildPeriodSelector($currency, $user->language);
                    $telegram->editMessageText(
                        $chatId,
                        $messageId,
                        $fullMessage,
                        $keyboard
                    );
                } catch (\Exception $e) {
                    // If edit fails, send new message
                    \Log::warning('Failed to edit history message, sending new one', ['error' => $e->getMessage()]);
                    // Add main menu button to keyboard
                    $keyboard = CurrencyKeyboard::buildPeriodSelector($currency, $user->language);
                    $telegram->sendMessage(
                        $chatId,
                        $fullMessage,
                        $keyboard
                    );
                }
            } else {
                // Add main menu button to keyboard
                $keyboard = CurrencyKeyboard::buildPeriodSelector($currency, $user->language);
                $telegram->sendMessage(
                    $chatId,
                    $fullMessage,
                    $keyboard
                );
            }
        }
    }

    private function buildCaption(string $currency, int $days, array $trend, string $lang): string
    {
        $trendEmoji = match ($trend['trend']) {
            'up' => '📈',
            'down' => '📉',
            default => '➡️',
        };

        $trendText = match ($trend['trend']) {
            'up' => __('bot.history.trend_up', locale: $lang),
            'down' => __('bot.history.trend_down', locale: $lang),
            default => __('bot.history.trend_stable', locale: $lang),
        };

        $lines = [
            "📊 <b>{$currency}/UZS</b> - {$days} " . __('bot.history.days', locale: $lang),
            '',
            '📅 ' . __('bot.history.start', locale: $lang) . ': ' .
            number_format($trend['oldest_rate'], 2, '.', ' ') . ' UZS',
            '📅 ' . __('bot.history.end', locale: $lang) . ': ' .
            number_format($trend['latest_rate'], 2, '.', ' ') . ' UZS',
            '',
            "{$trendEmoji} {$trendText}: <b>" . sprintf('%+.2f%%', $trend['change_percent']) . '</b>',
        ];

        return implode("\n", $lines);
    }
}

