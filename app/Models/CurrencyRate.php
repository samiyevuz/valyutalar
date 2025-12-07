<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_code',
        'base_currency',
        'rate',
        'source',
        'rate_date',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_date' => 'date',
    ];

    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency_code', strtoupper($currency));
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('rate_date', $date);
    }

    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('rate_date', 'desc');
    }

    public static function getLatestRate(string $currency, string $source = 'cbu'): ?self
    {
        return self::forCurrency($currency)
            ->fromSource($source)
            ->latest()
            ->first();
    }

    public static function getHistoricalRates(
        string $currency,
        int $days = 30,
        string $source = 'cbu'
    ): Collection {
        $startDate = now('Asia/Tashkent')->subDays($days)->startOfDay();
        
        return self::forCurrency($currency)
            ->fromSource($source)
            ->where('rate_date', '>=', $startDate)
            ->orderBy('rate_date', 'asc')
            ->get();
    }

    public static function getRatesForDate($date, string $source = 'cbu'): Collection
    {
        return self::forDate($date)
            ->fromSource($source)
            ->get();
    }

    public function getFormattedRate(int $decimals = 2): string
    {
        return number_format((float) $this->rate, $decimals, '.', ' ');
    }
}

