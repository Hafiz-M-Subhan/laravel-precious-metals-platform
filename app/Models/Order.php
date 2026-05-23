<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // Statuses
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FILLED     = 'filled';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_FAILED     = 'failed';

    // Sides
    const SIDE_BUY  = 'buy';
    const SIDE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'asset_id',
        'side',           // buy | sell
        'type',           // market | limit
        'quantity',       // in troy oz / grams
        'price_per_unit', // locked at execution
        'total_amount',   // quantity * price_per_unit
        'currency',
        'status',
        'filled_at',
        'rejected_reason',
        'metadata',
    ];

    protected $casts = [
        'quantity'       => 'decimal:6',
        'price_per_unit' => 'decimal:6',
        'total_amount'   => 'decimal:2',
        'filled_at'      => 'datetime',
        'metadata'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function portfolioEntry(): HasOne
    {
        return $this->hasOne(PortfolioEntry::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFilled($query)
    {
        return $query->where('status', self::STATUS_FILLED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
