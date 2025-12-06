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
use App\Services\ChartService;
use App\Services\CurrencyService;
use App\Services\TelegramService;

class HandleCallbackAction
{
    public function __construct(
        private CurrencyService $currencyService,
        private BankRatesService $bankRatesService,
        private AlertService $alertService,
        private ChartService $chartService,
    ) {}

    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        $callbackData = $update->getCallbackData();
        $callbackId = $update->getCallbackQueryId();
        $chatId = $update->getChatId();
        $messageId = $update->getCallbackMessageId();

        // Parse callback data
        $parts = explode(':', $callbackData);
        $action = $parts[0] ?? '';
        $param1 = $parts[1] ?? null;
        $param2 = $parts[2] ?? null;
        $param3 = $parts[3] ?? null;

        // Answer callback query first
        $telegram->answerCallbackQuery($callbackId);

        // Log callback for debugging
        \Log::info('Callback received', [
            'action' => $action,
            'param1' => $param1,
            'param2' => $param2,
            'callback_data' => $callbackData,
        ]);

        try {
            match ($action) {
                'lang' => $this->handleLanguageSelection($chatId, $messageId, $param1, $user, $telegram),
                'menu' => $this->handleMenuNavigation($chatId, $messageId, $param1, $user, $telegram),
                'rate' => $this->handleRateSelection($chatId, $param1, $user, $telegram),
                'banks' => $this->handleBanksSelection($chatId, $param1, $user, $telegram),
                'history' => $this->handleHistorySelection($chatId, $param1, $param2, $user, $telegram),
                'convert' => $this->handleConvertSelection($chatId, $param1, $param2, $user, $telegram),
                'alerts' => $this->handleAlertsAction($chatId, $messageId, $param1, $param2, $param3, $user, $telegram),
                'profile' => $this->handleProfileAction($chatId, $messageId, $param1, $user, $telegram),
                'favorites' => $this->handleFavoritesAction($chatId, $messageId, $param1, $param2, $user, $telegram),
                default => $this->handleUnknownAction($chatId, $action, $user, $telegram),
            };
        } catch (\Exception $e) {
            \Log::error('Callback action error', [
                'action' => $action,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $telegram->sendMessage(
                $chatId,
                '❌ ' . __('bot.errors.api_error', locale: $user->language),
                MainMenuKeyboard::build($user->language)
            );
        }
    }

    private function handleLanguageSelection(
        int $chatId,
        ?int $messageId,
        string $langCode,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
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
    }

    private function handleMenuNavigation(
        int $chatId,
        ?int $messageId,
        string $menu,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

        match ($menu) {
            'main' => $this->showMainMenu($chatId, $messageId, $user, $telegram),
            'rates' => $telegram->sendMessage(
                $chatId,
                '💱 ' . __('bot.rates.select_currency', locale: $lang),
                CurrencyKeyboard::build('rate', $lang)
            ),
            'convert' => $telegram->sendMessage(
                $chatId,
                '🔄 ' . __('bot.convert.select_from', locale: $lang),
                CurrencyKeyboard::buildForConversion('from', $lang)
            ),
            'banks' => $telegram->sendMessage(
                $chatId,
                '🏦 ' . __('bot.banks.select_currency', locale: $lang),
                CurrencyKeyboard::buildForBanks($lang)
            ),
            'history' => $telegram->sendMessage(
                $chatId,
                '📊 ' . __('bot.history.select_currency', locale: $lang),
                CurrencyKeyboard::buildForHistory($lang)
            ),
            'alerts' => $this->showAlertsMenu($chatId, $user, $telegram),
            'profile' => $this->showProfileMenu($chatId, $user, $telegram),
            'help' => app(HandleHelpAction::class)->execute(
                new TelegramUpdateDTO(0, null, null, null),
                $user,
                $telegram
            ),
            default => null,
        };
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
        $message .= "💱 " . __('bot.menu.convert', locale: $user->language) . "\n";
        $message .= "🏦 " . __('bot.menu.banks', locale: $user->language) . "\n";
        $message .= "📊 " . __('bot.menu.history', locale: $user->language) . "\n";
        $message .= "🔔 " . __('bot.menu.alerts', locale: $user->language) . "\n";
        $message .= "👤 " . __('bot.menu.profile', locale: $user->language) . "\n\n";
        $message .= __('bot.help.message', locale: $user->language);

        if ($messageId) {
            $telegram->editMessageText(
                $chatId,
                $messageId,
                $message,
                MainMenuKeyboard::build($user->language)
            );
        } else {
            $telegram->sendMessage($chatId, $message, MainMenuKeyboard::build($user->language));
        }
    }

    private function handleRateSelection(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        app(HandleRateAction::class, ['currencyService' => $this->currencyService])
            ->sendCurrencyRate($chatId, $currency, $user, $telegram);
    }

    private function handleBanksSelection(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        app(HandleBanksAction::class, ['bankRatesService' => $this->bankRatesService])
            ->showBankRates($chatId, $currency, $user, $telegram);
    }

    private function handleHistorySelection(
        int $chatId,
        string $currency,
        ?string $days,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        if (!$days) {
            // Show period selection
            $telegram->sendMessage(
                $chatId,
                '📊 ' . __('bot.history.select_period', ['currency' => $currency], $user->language),
                CurrencyKeyboard::buildPeriodSelector($currency, $user->language)
            );
            return;
        }

        app(HandleHistoryAction::class, [
            'currencyService' => $this->currencyService,
            'chartService' => $this->chartService,
        ])->showHistory($chatId, $currency, (int) $days, $user, $telegram);
    }

    private function handleConvertSelection(
        int $chatId,
        string $direction,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

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
    }

    private function handleAlertsAction(
        int $chatId,
        ?int $messageId,
        string $action,
        ?string $param1,
        ?string $param2,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $lang = $user->language;

        match ($action) {
            'create' => $this->showAlertCreation($chatId, $user, $telegram),
            'currency' => $this->showAlertCondition($chatId, $param1, $user, $telegram),
            'condition' => $this->startAlertAmountInput($chatId, $param1, $param2, $user, $telegram),
            'delete_menu' => $this->showAlertDeleteMenu($chatId, $user, $telegram),
            'delete' => $this->confirmDeleteAlert($chatId, (int) $param1, $user, $telegram),
            'confirm_delete' => $this->deleteAlert($chatId, (int) $param1, $user, $telegram),
            default => null,
        };
    }

    private function showAlertCreation(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $telegram->sendMessage(
            $chatId,
            '🔔 ' . __('bot.alerts.select_currency', locale: $user->language),
            AlertsKeyboard::buildCurrencySelector($user->language)
        );
    }

    private function showAlertCondition(
        int $chatId,
        string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
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
    }

    private function startAlertAmountInput(
        int $chatId,
        string $currency,
        string $condition,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
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
    }

    private function showAlertDeleteMenu(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $alerts = $this->alertService->getUserAlerts($user);

        $telegram->sendMessage(
            $chatId,
            '🗑️ ' . __('bot.alerts.select_to_delete', locale: $user->language),
            AlertsKeyboard::buildDeleteMenu($alerts, $user->language)
        );
    }

    private function confirmDeleteAlert(
        int $chatId,
        int $alertId,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
        $telegram->sendMessage(
            $chatId,
            '⚠️ ' . __('bot.alerts.confirm_delete', locale: $user->language),
            AlertsKeyboard::buildConfirmation($alertId, $user->language)
        );
    }

    private function deleteAlert(int $chatId, int $alertId, TelegramUser $user, TelegramService $telegram): void
    {
        $deleted = $this->alertService->deleteAlert($alertId, $user);

        $message = $deleted
            ? '✅ ' . __('bot.alerts.deleted', locale: $user->language)
            : '❌ ' . __('bot.alerts.delete_failed', locale: $user->language);

        $this->showAlertsMenu($chatId, $user, $telegram, $message);
    }

    private function showAlertsMenu(
        int $chatId,
        TelegramUser $user,
        TelegramService $telegram,
        ?string $prefix = null
    ): void {
        $alerts = $this->alertService->getUserAlerts($user);
        $message = $this->alertService->formatAlertsMessage($user);

        if ($prefix) {
            $message = $prefix . "\n\n" . $message;
        }

        $telegram->sendMessage($chatId, $message, AlertsKeyboard::build($alerts, $user->language));
    }

    private function handleProfileAction(
        int $chatId,
        ?int $messageId,
        string $action,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
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
    }

    private function toggleDigest(int $chatId, TelegramUser $user, TelegramService $telegram): void
    {
        $enabled = $user->toggleDigest();

        $message = $enabled
            ? '✅ ' . __('bot.profile.digest_enabled', locale: $user->language)
            : '🔕 ' . __('bot.profile.digest_disabled', locale: $user->language);

        $this->showProfileMenu($chatId, $user, $telegram, $message);
    }

    private function showProfileMenu(
        int $chatId,
        TelegramUser $user,
        TelegramService $telegram,
        ?string $prefix = null
    ): void {
        $action = app(HandleProfileAction::class);
        $update = new TelegramUpdateDTO(0, null, null, null);

        // Refresh user from DB
        $user->refresh();

        $action->execute($update, $user, $telegram);
    }

    private function handleFavoritesAction(
        int $chatId,
        ?int $messageId,
        string $action,
        ?string $currency,
        TelegramUser $user,
        TelegramService $telegram
    ): void {
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

