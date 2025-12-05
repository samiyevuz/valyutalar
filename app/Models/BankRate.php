<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BankRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_code',
        'bank_name',
        'currency_code',
        'buy_rate',
        'sell_rate',
        'rate_date',
    ];

    protected $casts = [
        'buy_rate' => 'decimal:6',
        'sell_rate' => 'decimal:6',
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

    public function scopeForBank($query, string $bankCode)
    {
        return $query->where('bank_code', $bankCode);
    }

    public static function getLatestRates(string $currency = 'USD'): Collection
    {
        $latestDate = self::forCurrency($currency)->max('rate_date');

        if (!$latestDate) {
            return collect();
        }

        return self::forCurrency($currency)
            ->forDate($latestDate)
            ->orderBy('buy_rate', 'desc')
            ->get();
    }

    public static function getBestBuyRate(string $currency = 'USD'): ?self
    {
        return self::getLatestRates($currency)
            ->sortByDesc('buy_rate')
            ->first();
    }

    public static function getBestSellRate(string $currency = 'USD'): ?self
    {
        return self::getLatestRates($currency)
            ->sortBy('sell_rate')
            ->first();
    }

    public function getSpread(): float
    {
        return (float) $this->sell_rate - (float) $this->buy_rate;
    }

    public function getSpreadPercent(): float
    {
        if ((float) $this->buy_rate === 0.0) {
            return 0;
        }

        return ($this->getSpread() / (float) $this->buy_rate) * 100;
    }

    public function getFormattedBuyRate(): string
    {
        return number_format((float) $this->buy_rate, 2, '.', ' ');
    }

    public function getFormattedSellRate(): string
    {
        return number_format((float) $this->sell_rate, 2, '.', ' ');
    }
}

