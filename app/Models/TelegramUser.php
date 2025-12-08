<?php

namespace App\Models;

use App\DTOs\TelegramUserDTO;
use App\Enums\Language;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'language',
        'favorite_currencies',
        'daily_digest_enabled',
        'digest_time',
        'state',
        'state_data',
        'is_blocked',
        'is_admin',
        'last_activity_at',
        'last_bot_message_id',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'favorite_currencies' => 'array',
        'daily_digest_enabled' => 'boolean',
        'state_data' => 'array',
        'is_blocked' => 'boolean',
        'is_admin' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'favorite_currencies' => '["USD", "EUR", "RUB"]',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function activeAlerts(): HasMany
    {
        return $this->alerts()->where('is_active', true)->where('is_triggered', false);
    }

    public function conversionHistories(): HasMany
    {
        return $this->hasMany(ConversionHistory::class);
    }

    public function getLanguageEnum(): Language
    {
        return Language::fromCode($this->language ?? 'en');
    }

    public function setLanguage(Language $language): void
    {
        $this->language = $language->value;
        $this->save();
    }

    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getDisplayName(): string
    {
        if ($this->first_name) {
            return $this->first_name;
        }

        if ($this->username) {
            return '@' . $this->username;
        }

        return 'User';
    }

    public function setState(?string $state, ?array $data = null): void
    {
        $this->state = $state;
        $this->state_data = $data;
        $this->save();
    }

    public function clearState(): void
    {
        $this->setState(null, null);
    }

    public function hasState(string $state): bool
    {
        return $this->state === $state;
    }

    public function updateActivity(): void
    {
        $this->last_activity_at = now();
        $this->saveQuietly();
    }

    public function getFavoriteCurrencies(): array
    {
        return $this->favorite_currencies ?? ['USD', 'EUR', 'RUB'];
    }

    public function setFavoriteCurrencies(array $currencies): void
    {
        $this->favorite_currencies = array_values(array_unique($currencies));
        $this->save();
    }

    public function toggleDigest(): bool
    {
        $this->daily_digest_enabled = !$this->daily_digest_enabled;
        $this->save();
        return $this->daily_digest_enabled;
    }

    public static function findByTelegramId(int $telegramId): ?self
    {
        return self::where('telegram_id', $telegramId)->first();
    }

    public static function findOrCreateFromDTO(TelegramUserDTO $dto): self
    {
        return self::updateOrCreate(
            ['telegram_id' => $dto->id],
            [
                'username' => $dto->username,
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'last_activity_at' => now(),
            ]
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeWithDigestEnabled($query)
    {
        return $query->where('daily_digest_enabled', true);
    }
}

