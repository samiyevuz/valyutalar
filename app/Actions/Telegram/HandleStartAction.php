<?php

namespace App\Actions\Telegram;

use App\Builders\Keyboard\LanguageKeyboard;
use App\Builders\Keyboard\MainMenuKeyboard;
use App\DTOs\TelegramUpdateDTO;
use App\Models\TelegramUser;
use App\Services\TelegramService;

class HandleStartAction
{
    public function execute(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        \Log::info('HandleStartAction execute', [
            'chat_id' => $update->getChatId(),
            'user_id' => $user->id,
            'was_recently_created' => $user->wasRecentlyCreated ?? false,
            'language' => $user->language,
        ]);

        try {
            // If language not set, show language selection
            if (empty($user->language)) {
                \Log::info('Showing language selection', [
                    'has_language' => !empty($user->language),
                ]);
                $this->showLanguageSelection($update, $telegram);
                return;
            }

            // Always show welcome message when /start is called
            \Log::info('Showing welcome message', [
                'chat_id' => $update->getChatId(),
                'language' => $user->language,
            ]);
            $this->showWelcomeMessage($update, $user, $telegram);
        } catch (\Exception $e) {
            \Log::error('HandleStartAction error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            
            // Try to send error message
            try {
                $telegram->sendMessage(
                    $update->getChatId(),
                    '❌ ' . __('bot.errors.api_error', locale: $user->language ?? 'en')
                );
            } catch (\Exception $sendError) {
                \Log::error('Failed to send error message in HandleStartAction', [
                    'error' => $sendError->getMessage(),
                ]);
            }
            
            throw $e;
        }
    }

    private function showLanguageSelection(TelegramUpdateDTO $update, TelegramService $telegram): void
    {
        $message = "🌍 <b>Welcome! / Добро пожаловать! / Xush kelibsiz!</b>\n\n";
        $message .= "Please select your language:\n";
        $message .= "Пожалуйста, выберите язык:\n";
        $message .= "Iltimos, tilni tanlang:";

        $telegram->sendMessage(
            $update->getChatId(),
            $message,
            LanguageKeyboard::build()
        );
    }

    private function showWelcomeMessage(TelegramUpdateDTO $update, TelegramUser $user, TelegramService $telegram): void
    {
        try {
            $name = $user->getDisplayName();
            $chatId = $update->getChatId();
            $lang = $user->language;

            \Log::info('Building welcome message', [
                'chat_id' => $chatId,
                'name' => $name,
                'language' => $lang,
            ]);

            // Escape HTML in name to prevent parsing errors
            $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $message = __('bot.welcome', ['name' => $safeName], $lang) . "\n\n";
            $message .= "💱 " . __('bot.menu.rates', locale: $lang) . "\n";
            $message .= "🔄 " . __('bot.menu.convert', locale: $lang) . "\n";
            $message .= "🏦 " . __('bot.menu.banks', locale: $lang) . "\n";
            $message .= "📊 " . __('bot.menu.history', locale: $lang) . "\n";
            $message .= "🔔 " . __('bot.menu.alerts', locale: $lang) . "\n";
            $message .= "👤 " . __('bot.menu.profile', locale: $lang) . "\n\n";
            $message .= __('bot.help.message', locale: $lang);
            
            // Note: cleanText is automatically called in sendMessage

            \Log::info('Welcome message built', [
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 100),
            ]);

            $keyboard = MainMenuKeyboard::build($lang);
            \Log::info('Keyboard built', ['keyboard' => $keyboard]);

            $result = $telegram->sendMessage(
                $chatId,
                $message,
                $keyboard
            );

            \Log::info('Welcome message sent', [
                'chat_id' => $chatId,
                'result_ok' => $result['ok'] ?? false,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in showWelcomeMessage', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);
            throw $e;
        }
    }
}

