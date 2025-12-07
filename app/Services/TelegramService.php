<?php

namespace App\Services;

use App\DTOs\TelegramUpdateDTO;
use App\Exceptions\TelegramException;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $apiUrl;
    private string $token;

    public function __construct()
    {
        $token = env('TELEGRAM_BOT_TOKEN') ?: config('telegram.bot_token');
        
        Log::info('TelegramService constructor', [
            'token_set' => !empty($token),
            'token_length' => $token ? strlen($token) : 0,
            'api_url' => config('telegram.api_url'),
            'env_token_set' => !empty(env('TELEGRAM_BOT_TOKEN')),
        ]);
        
        if (empty($token)) {
            $error = 'Telegram bot token is empty. Please set TELEGRAM_BOT_TOKEN in .env file.';
            Log::error($error);
            error_log('[TELEGRAM] ERROR: ' . $error);
            throw TelegramException::invalidToken();
        }
        
        $this->token = $token;
        $this->apiUrl = config('telegram.api_url') . $this->token;
        
        Log::info('TelegramService initialized', [
            'api_url' => substr($this->apiUrl, 0, 50) . '...',
        ]);
    }

    public function sendMessage(
        int $chatId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML',
        bool $disableWebPagePreview = true,
        ?int $replyToMessageId = null
    ): array {
        Log::info('TelegramService::sendMessage', [
            'chat_id' => $chatId,
            'text_length' => strlen($text),
            'has_reply_markup' => !empty($replyMarkup),
        ]);

        try {
            // Clean text - replace escaped newlines with actual newlines
            $text = $this->cleanText($text);

            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => $disableWebPagePreview,
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            if ($replyToMessageId) {
                $payload['reply_to_message_id'] = $replyToMessageId;
            }

            $result = $this->request('sendMessage', $payload);
            
            Log::info('TelegramService::sendMessage result', [
                'chat_id' => $chatId,
                'ok' => $result['ok'] ?? false,
                'error' => $result['error'] ?? null,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('TelegramService::sendMessage exception', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    public function sendPhoto(
        int $chatId,
        string $photo,
        ?string $caption = null,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML'
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => $parseMode,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendPhoto', $payload);
    }

    public function editMessageText(
        int $chatId,
        int $messageId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML'
    ): array {
        // Clean text - replace escaped newlines with actual newlines
        $text = $this->cleanText($text);

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('editMessageText', $payload);
    }

    public function editMessageReplyMarkup(
        int $chatId,
        int $messageId,
        ?array $replyMarkup = null
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('editMessageReplyMarkup', $payload);
    }

    public function deleteMessage(int $chatId, int $messageId): array
    {
        return $this->request('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false,
        int $cacheTime = 0
    ): array {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
            'cache_time' => $cacheTime,
        ];

        if ($text) {
            $payload['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $payload);
    }

    public function sendChatAction(int $chatId, string $action = 'typing'): array
    {
        return $this->request('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query', 'edited_message'],
            'drop_pending_updates' => true,
        ];

        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->request('setWebhook', $payload);
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        return $this->request('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    public function getMe(): array
    {
        return $this->request('getMe');
    }

    public function setMyCommands(array $commands, ?string $languageCode = null): array
    {
        $payload = [
            'commands' => $commands,
        ];

        if ($languageCode) {
            $payload['language_code'] = $languageCode;
        }

        return $this->request('setMyCommands', $payload);
    }

    public function getBotCommands(): array
    {
        return [
            ['command' => 'start', 'description' => 'Start the bot'],
            ['command' => 'help', 'description' => 'Get help'],
            ['command' => 'rate', 'description' => 'View exchange rates'],
            ['command' => 'convert', 'description' => 'Convert currency'],
            ['command' => 'history', 'description' => 'View rate history'],
            ['command' => 'banks', 'description' => 'Bank exchange rates'],
            ['command' => 'alerts', 'description' => 'Manage price alerts'],
            ['command' => 'profile', 'description' => 'Your profile'],
        ];
    }

    public function registerCommands(): void
    {
        $commands = $this->getBotCommands();

        // English commands
        $this->setMyCommands($commands, 'en');

        // Russian commands
        $this->setMyCommands([
            ['command' => 'start', 'description' => 'Запустить бота'],
            ['command' => 'help', 'description' => 'Помощь'],
            ['command' => 'rate', 'description' => 'Курсы валют'],
            ['command' => 'convert', 'description' => 'Конвертировать валюту'],
            ['command' => 'history', 'description' => 'История курсов'],
            ['command' => 'banks', 'description' => 'Курсы банков'],
            ['command' => 'alerts', 'description' => 'Уведомления о курсах'],
            ['command' => 'profile', 'description' => 'Ваш профиль'],
        ], 'ru');

        // Uzbek commands
        $this->setMyCommands([
            ['command' => 'start', 'description' => 'Botni ishga tushirish'],
            ['command' => 'help', 'description' => 'Yordam'],
            ['command' => 'rate', 'description' => 'Valyuta kurslari'],
            ['command' => 'convert', 'description' => 'Valyutani aylantirish'],
            ['command' => 'history', 'description' => 'Kurslar tarixi'],
            ['command' => 'banks', 'description' => 'Bank kurslari'],
            ['command' => 'alerts', 'description' => 'Kurs xabarnomalari'],
            ['command' => 'profile', 'description' => 'Sizning hisobingiz'],
        ], 'uz');
    }

    private function request(string $method, array $payload = []): array
    {
        $url = "{$this->apiUrl}/{$method}";
        
        Log::info("Telegram API request", [
            'method' => $method,
            'url' => $url,
            'payload_keys' => array_keys($payload),
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->post($url, $payload);

            $data = $response->json();
            $statusCode = $response->status();

            Log::info("Telegram API response", [
                'method' => $method,
                'status' => $statusCode,
                'ok' => $data['ok'] ?? false,
                'error_code' => $data['error_code'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            if (!$response->successful() || !($data['ok'] ?? false)) {
                Log::error("Telegram API error: {$method}", [
                    'payload' => $this->sanitizePayload($payload),
                    'response' => $data,
                    'status' => $statusCode,
                    'body' => $response->body(),
                ]);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error("Telegram API exception: {$method}", [
                'url' => $url,
                'payload' => $this->sanitizePayload($payload),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function cleanText(string $text): string
    {
        // Replace escaped newlines with actual newlines (handle both \\n and \n)
        $text = str_replace(['\\n', '\\\\n'], ["\n", "\\n"], $text);
        
        // Fix HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Telegram HTML only supports: <b>, <i>, <u>, <s>, <code>, <pre>, <a>
        // Remove any empty tags
        $text = preg_replace('/<b\s*\/?>\s*<\/b>/i', '', $text);
        $text = preg_replace('/<i\s*\/?>\s*<\/i>/i', '', $text);
        $text = preg_replace('/<u\s*\/?>\s*<\/u>/i', '', $text);
        $text = preg_replace('/<s\s*\/?>\s*<\/s>/i', '', $text);
        $text = preg_replace('/<code\s*\/?>\s*<\/code>/i', '', $text);
        $text = preg_replace('/<pre\s*\/?>\s*<\/pre>/i', '', $text);
        
        // Remove any self-closing tags that Telegram doesn't support
        $text = preg_replace('/<(b|i|u|s|code|pre)\s*\/>/i', '<$1>', $text);
        
        // Remove any invalid characters that might break HTML parsing
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Fix unclosed tags - ensure all opening tags have closing tags
        // Use a more robust approach: parse tags and fix them
        $allowedTags = ['b', 'i', 'u', 's', 'code', 'pre'];
        foreach ($allowedTags as $tag) {
            // Find all opening tags (not self-closing)
            preg_match_all("/<{$tag}(?:\s+[^>]*)?(?<!\/)>/i", $text, $openMatches, PREG_OFFSET_CAPTURE);
            // Find all closing tags
            preg_match_all("/<\/{$tag}>/i", $text, $closeMatches, PREG_OFFSET_CAPTURE);
            
            $openCount = count($openMatches[0]);
            $closeCount = count($closeMatches[0]);
            
            if ($openCount > $closeCount) {
                // Add missing closing tags at the end
                $text .= str_repeat("</{$tag}>", $openCount - $closeCount);
            } elseif ($closeCount > $openCount) {
                // Remove extra closing tags from the end
                $text = preg_replace("/<\/{$tag}>/i", '', $text, $closeCount - $openCount);
            }
        }
        
        // Final validation: ensure no broken HTML tags
        // Remove any tags that are not properly closed (except allowed ones)
        // But be careful - don't remove valid tags
        $text = preg_replace('/<(?!\/?(?:b|i|u|s|code|pre|a)\b)[^>]*>/i', '', $text);
        
        // Ensure no stray < or > characters that could break parsing
        // But preserve valid HTML tags
        $text = preg_replace('/(?<!<)(?<!<\/)(?<![a-z])[<>](?![a-z\/])/i', '', $text);
        
        // Final check: ensure all <b> tags are properly closed
        // Count <b> and </b> tags
        $bOpenCount = substr_count(strtolower($text), '<b>');
        $bCloseCount = substr_count(strtolower($text), '</b>');
        if ($bOpenCount > $bCloseCount) {
            $text .= str_repeat('</b>', $bOpenCount - $bCloseCount);
        }
        
        return trim($text);
    }

    private function sanitizePayload(array $payload): array
    {
        // Remove sensitive data from logs
        $sensitized = $payload;

        if (isset($sensitized['photo']) && strlen($sensitized['photo']) > 100) {
            $sensitized['photo'] = '[BINARY_DATA]';
        }

        return $sensitized;
    }
}

