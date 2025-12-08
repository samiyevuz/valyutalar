<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\AlertsKeyboard;
use App\Builders\Keyboard\CurrencyKeyboard;
use App\Builders\Keyboard\LanguageKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\Builders\Keyboard\ProfileKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Enums\Language;
use App\Models\TelegramUser;
use App\Services\AlertService;
use App\Services\BankRatesService;
use App\Services\CurrencyService;
use App\Services\TelegramService;

class HandleCallbackAction
{
    private ?TelegramUpdateDTO $currentUpdate = null;

    public function __construct(
        private CurrencyService $currencyService,
        private BankRatesService $bankRatesService,
        private AlertService $alertService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        // Store current update for use in sub-methods
        $this->currentUpdate = $update;
        
        $callbackData = $update->getCallbackData();
        $callbackId = $update->getCallbackQueryId();
        $chatId = $update->getChatId();
        $messageId = $update->getCallbackMessageId();

        // Log callback for debugging
        \Log::info('Callback execute started', [
            'callback_data' => $callbackData,
            'callback_id' => $callbackId,
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        if (empty($callbackData)) {
            \Log::error('Empty callback data');
            return;
        }

        // Parse callback data
        $parts = explode(':', $callbackData);
        $action = $parts[0] ?? '';
        $param1 = $parts[1] ?? null;
        $param2 = $parts[2] ?? null;
        $param3 = $parts[3] ?? null;

        // Answer callback query first
        try {
            $telegram->answerCallbackQuery($callbackId);
        } catch (\Exception $e) {
            \Log::error('Failed to answer callback query', [
                'error' => $e->getMessage(),
                'callback_id' => $callbackId,
            ]);
        }

        // Log callback for debugging
        \Log::info('Callback received', [
            'action' => $action,
            'param1' => $param1,
            'param2' => $param2,
            'callback_data' => $callbackData,
        ]);

        try {
            \Log::info('Executing callback action', ['action' => $action]);
            
            match ($action) {
                'lang' => $this->handleLanguageSelection($chatId, $messageId, $param1, $user, $telegram),
                'menu' => $this->handleMenuNavigation($chatId, $messageId, $param1, $user, $telegram),
                'rate' => $this->handleRateSelection($chatId, $param1, $user, $telegram),
                'banks' => $this->handleBanksSelection($chatId, $param1, $user, $telegram),
                'convert' => $this->handleConvertSelection($chatId, $param1, $param2, $user, $telegram),
                'alerts' => $this->handleAlertsAction($chatId, $messageId, $param1, $param2, $param3, $user, $telegram),
                'profile' => $this->handleProfileAction($chatId, $messageId, $param1, $user, $telegram),
                'favorites' => $this->handleFavoritesAction($chatId, $messageId, $param1, $param2, $user, $telegram),
                default => $this->handleUnknownAction($chatId, $action, $user, $telegram),
            };
            
            \Log::info('Callback action executed successfully', ['action' => $action]);
        } catch (\Exception $e) {
            \Log::error('Callback action error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                $telegram->sendMessage(
                    $chatId,
                    '❌ ' . __('bot.errors.api_error', locale: $user->language),
                    MainMenuKeyboard::build($user->language)
                );
            } catch (\Exception $sendError) {
                \Log::error('Failed to send error message', [
                    'error' => $sendError->getMessage(),
                ]);
            }
        }
    }

    private function handleLanguageSelection(
        int $chatId,
        ?int $messageId,
        ?string $langCode,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($langCode)) {
            \Log::warning('Language selection: langCode is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $language = Language::fromCode($langCode);
            $user->setLanguage($language);

            app()->setLocale($langCode);

            $message = "✅ " . __('bot.language.changed', ['language' => $language->label()], $langCode) . "\n\n";
            $message .= __('bot.welcome', ['name' => $user->getDisplayName()], $langCode);

            if ($messageId) {
                $telegram->editMessageText(
                    $chatId,
                    $messageId,
                    $message,
                    MainMenuKeyboard::build($langCode)
                );
            } else {
                $telegram->sendMessage($chatId, $message, MainMenuKeyboard::build($langCode));
            }
        } catch (\Exception $e) {
            \Log::error('Language selection error', [
                'lang_code' => $langCode,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleMenuNavigation(
        int $chatId,
        ?int $messageId,
        string $menu,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

        \Log::info('Handling menu navigation', ['menu' => $menu, 'lang' => $lang, 'message_id' => $messageId]);

        match ($menu) {
            'main' => $this->showMainMenu($chatId, $messageId, $user, $telegram),
            'rates' => $this->sendOrEditMessage(
                $chatId,
                $messageId,
                '💱 ' . __('bot.rates.select_currency', locale: $lang),
                CurrencyKeyboard::build('rate', $lang),
                $telegram
            ),
            'convert' => $this->sendOrEditMessage(
                $chatId,
                $messageId,
                '🔄 ' . __('bot.convert.select_from', locale: $lang),
                CurrencyKeyboard::buildForConversion('from', $lang),
                $telegram
            ),
            'banks' => $this->sendOrEditMessage(
                $chatId,
                $messageId,
                '🏦 ' . __('bot.banks.select_currency', locale: $lang),
                CurrencyKeyboard::buildForBanks($lang),
                $telegram
            ),
            'alerts' => $this->showAlertsMenu($chatId, $messageId, $user, $telegram),
            default => \Log::warning('Unknown menu', ['menu' => $menu]),
        };
    }

    private function sendOrEditMessage(
        int $chatId,
        ?int $messageId,
        string $text,
        ?array $replyMarkup,
        TelegramService $telegram
    ): void {
        if ($messageId) {
            try {
                $telegram->editMessageText($chatId, $messageId, $text, $replyMarkup);
            } catch (\Exception $e) {
                // If edit fails (e.g., message too old), delete old message and send new one
                \Log::warning('Failed to edit message, deleting and sending new one', [
                    'error' => $e->getMessage(),
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);
                try {
                    $telegram->deleteMessage($chatId, $messageId);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                    \Log::debug('Failed to delete old message', ['error' => $deleteError->getMessage()]);
                }
                $telegram->sendMessage($chatId, $text, $replyMarkup);
            }
        } else {
            $telegram->sendMessage($chatId, $text, $replyMarkup);
        }
    }

    private function showMainMenu(
        int $chatId,
        ?int $messageId,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $name = $user->getDisplayName();
        $message = __('bot.welcome', ['name' => $name], $user->language) . "\n\n";
        $message .= "💱 " . __('bot.menu.rates', locale: $user->language) . "\n";
        $message .= "🔄 " . __('bot.menu.convert', locale: $user->language) . "\n";
        $message .= "🏦 " . __('bot.menu.banks', locale: $user->language) . "\n";
        $message .= "📊 " . __('bot.menu.history', locale: $user->language) . "\n";
        $message .= "🔔 " . __('bot.menu.alerts', locale: $user->language);

        $this->sendOrEditMessage(
            $chatId,
            $messageId,
            $message,
            MainMenuKeyboard::build($user->language),
            $telegram
        );
    }

    private function handleRateSelection(
        int $chatId,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($currency)) {
            \Log::warning('Rate selection: currency is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.currency_not_found', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $messageId = $this->currentUpdate?->getCallbackMessageId();
            $action = app(HandleRateAction::class);
            $action->sendCurrencyRate($chatId, $currency, $user, $telegram, $messageId);
        } catch (\Exception $e) {
            \Log::error('Rate selection error', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleBanksSelection(
        int $chatId,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($currency)) {
            \Log::warning('Banks selection: currency is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.currency_not_found', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $messageId = $this->currentUpdate?->getCallbackMessageId();
            $action = app(HandleBanksAction::class);
            $action->showBankRates($chatId, $currency, $user, $telegram, $messageId);
        } catch (\Exception $e) {
            \Log::error('Banks selection error', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }


    private function handleConvertSelection(
        int $chatId,
        ?string $direction,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

        if (empty($direction) || empty($currency)) {
            \Log::warning('Convert selection: direction or currency is empty', [
                'direction' => $direction,
                'currency' => $currency,
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $lang),
                MainMenuKeyboard::build($lang)
            );
            return;
        }

        try {
            if ($direction === 'from') {
                $user->setState('convert_awaiting_to', ['from' => $currency]);

                $telegram->sendMessage(
                    $chatId,
                    '🔄 ' . __('bot.convert.select_to', ['currency' => $currency], $lang),
                    CurrencyKeyboard::buildForConversion('to', $lang)
                );
            } elseif ($direction === 'to') {
                $stateData = $user->state_data ?? [];
                $fromCurrency = $stateData['from'] ?? 'USD';

                if (empty($fromCurrency)) {
                    $fromCurrency = 'USD';
                }

                $user->setState('convert_awaiting_amount', [
                    'from' => $fromCurrency,
                    'to' => $currency,
                ]);

                $telegram->sendMessage(
                    $chatId,
                    '💰 ' . __('bot.convert.enter_amount', [
                        'from' => $fromCurrency,
                        'to' => $currency,
                    ], $lang)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Convert selection error', [
                'direction' => $direction,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $lang),
                MainMenuKeyboard::build($lang)
            );
        }
    }

    private function handleAlertsAction(
        int $chatId,
        ?int $messageId,
        ?string $action,
        ?string $param1,
        ?string $param2,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

        if (empty($action)) {
            \Log::warning('Alerts action: action is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $lang),
                MainMenuKeyboard::build($lang)
            );
            return;
        }

        try {
            match ($action) {
                'create' => $this->showAlertCreation($chatId, $user, $telegram),
                'currency' => $this->showAlertCondition($chatId, $param1 ?? null, $user, $telegram),
                'condition' => $this->startAlertAmountInput($chatId, $param1 ?? null, $param2 ?? null, $user, $telegram),
                'delete_menu' => $this->showAlertDeleteMenu($chatId, $user, $telegram),
                'delete' => $this->confirmDeleteAlert($chatId, (int) ($param1 ?? 0), $user, $telegram),
                'confirm_delete' => $this->deleteAlert($chatId, (int) ($param1 ?? 0), $user, $telegram),
                default => null,
            };
        } catch (\Exception $e) {
            \Log::error('Alerts action error', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $lang),
                MainMenuKeyboard::build($lang)
            );
        }
    }

    private function showAlertCreation(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        try {
            $telegram->sendMessage(
                $chatId,
                '🔔 ' . __('bot.alerts.select_currency', locale: $user->language),
                AlertsKeyboard::buildCurrencySelector($user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Show alert creation error', ['error' => $e->getMessage()]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function showAlertCondition(
        int $chatId,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($currency)) {
            \Log::warning('Show alert condition: currency is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.currency_not_found', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $currentRate = $this->currencyService->getRate($currency);
            $rateText = $currentRate ? number_format($currentRate->rate, 2, '.', ' ') : '—';

            $message = "🔔 <b>{$currency}/UZS</b>\n\n";
            $message .= __('bot.alerts.current_rate', locale: $user->language) . ": <b>{$rateText}</b> UZS\n\n";
            $message .= __('bot.alerts.select_condition', locale: $user->language);

            $telegram->sendMessage(
                $chatId,
                $message,
                AlertsKeyboard::buildConditionSelector($currency, $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Show alert condition error', [
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function startAlertAmountInput(
        int $chatId,
        ?string $currency,
        ?string $condition,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($currency) || empty($condition)) {
            \Log::warning('Start alert amount input: currency or condition is empty', [
                'currency' => $currency,
                'condition' => $condition,
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $user->setState('alert_awaiting_amount', [
                'currency' => $currency,
                'condition' => $condition,
            ]);

            $conditionText = $condition === 'above'
                ? __('bot.alerts.above', locale: $user->language)
                : __('bot.alerts.below', locale: $user->language);

            $telegram->sendMessage(
                $chatId,
                '💰 ' . __('bot.alerts.enter_amount', [
                    'currency' => $currency,
                    'condition' => $conditionText,
                ], $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Start alert amount input error', [
                'currency' => $currency,
                'condition' => $condition,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function showAlertDeleteMenu(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        try {
            $alerts = $this->alertService->getUserAlerts($user);

            $telegram->sendMessage(
                $chatId,
                '🗑️ ' . __('bot.alerts.select_to_delete', locale: $user->language),
                AlertsKeyboard::buildDeleteMenu($alerts, $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Show alert delete menu error', ['error' => $e->getMessage()]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function confirmDeleteAlert(
        int $chatId,
        int $alertId,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if ($alertId <= 0) {
            \Log::warning('Confirm delete alert: invalid alert ID', ['alert_id' => $alertId]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $telegram->sendMessage(
                $chatId,
                '⚠️ ' . __('bot.alerts.confirm_delete', locale: $user->language),
                AlertsKeyboard::buildConfirmation($alertId, $user->language)
            );
        } catch (\Exception $e) {
            \Log::error('Confirm delete alert error', [
                'alert_id' => $alertId,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function deleteAlert(int $chatId, int $alertId, TelegramUser $user, TelegramService $telegram): void
    {
        if ($alertId <= 0) {
            \Log::warning('Delete alert: invalid alert ID', ['alert_id' => $alertId]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            $deleted = $this->alertService->deleteAlert($alertId, $user);

            $message = $deleted
                ? '✅ ' . __('bot.alerts.deleted', locale: $user->language)
                : '❌ ' . __('bot.alerts.delete_failed', locale: $user->language);

            $messageId = $this->currentUpdate?->getCallbackMessageId();
            $this->showAlertsMenu($chatId, $messageId, $user, $telegram, $message);
        } catch (\Exception $e) {
            \Log::error('Delete alert error', [
                'alert_id' => $alertId,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function showAlertsMenu(
        int $chatId,
        ?int $messageId,
        TelegramUser $user,
        TelegramService $telegram,
        ?string $prefix = null
    ): void {
        try {
            $alerts = $this->alertService->getUserAlerts($user);
            $message = $this->alertService->formatAlertsMessage($user);

            if ($prefix) {
                $message = $prefix . "\n\n" . $message;
            }

            $this->sendOrEditMessage($chatId, $messageId, $message, AlertsKeyboard::build($alerts, $user->language), $telegram);
        } catch (\Exception $e) {
            \Log::error('Show alerts menu error', ['error' => $e->getMessage()]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleProfileAction(
        int $chatId,
        ?int $messageId,
        ?string $action,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($action)) {
            \Log::warning('Profile action: action is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            match ($action) {
                'language' => $telegram->sendMessage(
                    $chatId,
                    '🌐 ' . __('bot.profile.select_language', locale: $user->language),
                    LanguageKeyboard::buildWithBack($user->language)
                ),
                'favorites' => app(HandleFavoritesAction::class)->execute(
                    new TelegramUpdateDTO(0, null, null, null),
                    $user,
                    $telegram
                ),
                'toggle_digest' => $this->toggleDigest($chatId, $user, $telegram),
                default => null,
            };
        } catch (\Exception $e) {
            \Log::error('Profile action error', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function toggleDigest(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        try {
            $enabled = $user->toggleDigest();

            $message = $enabled
                ? '✅ ' . __('bot.profile.digest_enabled', locale: $user->language)
                : '🔕 ' . __('bot.profile.digest_disabled', locale: $user->language);

            $messageId = $this->currentUpdate?->getCallbackMessageId();
            $this->showProfileMenu($chatId, $messageId, $user, $telegram, $message);
        } catch (\Exception $e) {
            \Log::error('Toggle digest error', ['error' => $e->getMessage()]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function showProfileMenu(
        int $chatId,
        ?int $messageId,
        TelegramUser $user,
        TelegramService $telegram,
        ?string $prefix = null
    ): void {
        try {
            // Refresh user from DB
            $user->refresh();
            
            $action = app(HandleProfileAction::class);
            $update = new TelegramUpdateDTO(0, null, null, null);
            
            // Execute profile action - it will handle messageId internally if needed
            $action->execute($update, $user, $telegram);
        } catch (\Exception $e) {
            \Log::error('Show profile menu error', ['error' => $e->getMessage()]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleFavoritesAction(
        int $chatId,
        ?int $messageId,
        ?string $action,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (empty($action)) {
            \Log::warning('Favorites action: action is empty');
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
            return;
        }

        try {
            if ($action === 'toggle' && $currency) {
                $favorites = $user->getFavoriteCurrencies();

                if (in_array($currency, $favorites)) {
                    $favorites = array_values(array_diff($favorites, [$currency]));
                } else {
                    $favorites[] = $currency;
                }

                $user->setFavoriteCurrencies($favorites);

                // Update keyboard
                if ($messageId) {
                    $telegram->editMessageReplyMarkup(
                        $chatId,
                        $messageId,
                        ProfileKeyboard::buildFavoritesEditor($favorites, $user->language)
                    );
                }
            } elseif ($action === 'save') {
                $telegram->sendMessage(
                    $chatId,
                    '✅ ' . __('bot.favorites.saved', locale: $user->language),
                    MainMenuKeyboard::build($user->language)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Favorites action error', [
                'action' => $action,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);
            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleUnknownAction(
        int $chatId,
        string $action,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        \Log::warning('Unknown callback action', ['action' => $action]);
        
        $telegram->sendMessage(
            $chatId,
            '❌ ' . __('bot.errors.api_error', locale: $user->language),
            MainMenuKeyboard::build($user->language)
        );
    }
}

