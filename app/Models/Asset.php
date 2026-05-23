<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'symbol',       // XAU, XAG, XPT, XPD
        'name',         // Gold, Silver, Platinum, Palladium
        'unit',         // troy_oz, gram, kg
        'currency',     // EUR, USD
        'spot_price',
        'bid_price',
        'ask_price',
        'spread',
        'daily_change',
        'daily_change_pct',
        'is_active',
        'metadata',     // JSON: purity options, mintage info, etc.
    ];

    protected $casts = [
        'spot_price'       => 'decimal:6',
        'bid_price'        => 'decimal:6',
        'ask_price'        => 'decimal:6',
        'spread'           => 'decimal:6',
        'daily_change'     => 'decimal:6',
        'daily_change_pct' => 'decimal:4',
        'is_active'        => 'boolean',
        'metadata'         => 'array',
    ];

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function savingsPlans(): HasMany
    {
        return $this->hasMany(SavingsPlan::class);
    }

    /** Latest OHLCV candle for charting (1h resolution). */
    public function latestCandle(): HasMany
    {
        return $this->priceHistory()
            ->where('resolution', '1h')
            ->orderByDesc('recorded_at')
            ->limit(1);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
