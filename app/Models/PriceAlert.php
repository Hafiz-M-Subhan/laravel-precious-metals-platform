<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    const CONDITION_ABOVE = 'above';
    const CONDITION_BELOW = 'below';

    protected $fillable = [
        'user_id',
        'asset_id',
        'condition',
        'target_price',
        'note',
        'is_active',
        'triggered_at',
    ];

    protected $casts = [
        'target_price' => 'decimal:6',
        'is_active'    => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('triggered_at');
    }

    public function isTriggered(float $currentPrice): bool
    {
        return match ($this->condition) {
            self::CONDITION_ABOVE => $currentPrice >= (float) $this->target_price,
            self::CONDITION_BELOW => $currentPrice <= (float) $this->target_price,
            default               => false,
        };
    }
}
