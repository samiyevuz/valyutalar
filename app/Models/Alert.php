<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'currency_from',
        'currency_to',
        'condition',
        'target_rate',
        'is_active',
        'is_triggered',
        'triggered_at',
        'triggered_rate',
    ];

    protected $casts = [
        'target_rate' => 'decimal:6',
        'triggered_rate' => 'decimal:6',
        'is_active' => 'boolean',
        'is_triggered' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_triggered', false);
    }

    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency_from', strtoupper($currency));
    }

    public function checkCondition(float $currentRate): bool
    {
        return match ($this->condition) {
            'above' => $currentRate >= (float) $this->target_rate,
            'below' => $currentRate <= (float) $this->target_rate,
            default => false,
        };
    }

    public function trigger(float $rate): void
    {
        $this->is_triggered = true;
        $this->triggered_at = now();
        $this->triggered_rate = $rate;
        $this->save();
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    public function reactivate(): void
    {
        $this->is_active = true;
        $this->is_triggered = false;
        $this->triggered_at = null;
        $this->triggered_rate = null;
        $this->save();
    }

    public function getConditionSymbol(): string
    {
        return $this->condition === 'above' ? '>' : '<';
    }

    public function getDescription(): string
    {
        return sprintf(
            '%s/%s %s %s',
            $this->currency_from,
            $this->currency_to,
            $this->getConditionSymbol(),
            number_format((float) $this->target_rate, 2)
        );
    }

    public function getStatusEmoji(): string
    {
        if ($this->is_triggered) {
            return '✅';
        }

        if (!$this->is_active) {
            return '⏸️';
        }

        return '🔔';
    }
}

