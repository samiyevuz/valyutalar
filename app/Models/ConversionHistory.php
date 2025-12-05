<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
        'currency_from',
        'currency_to',
        'amount_from',
        'amount_to',
        'rate_used',
    ];

    protected $casts = [
        'amount_from' => 'decimal:2',
        'amount_to' => 'decimal:2',
        'rate_used' => 'decimal:6',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function getFormattedResult(): string
    {
        return sprintf(
            '%s %s → %s %s',
            number_format((float) $this->amount_from, 2, '.', ' '),
            $this->currency_from,
            number_format((float) $this->amount_to, 2, '.', ' '),
            $this->currency_to
        );
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('telegram_user_id', $userId);
    }
}

