<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Automated savings plan: user defines a monthly amount and asset;
 * the system executes market buy orders on the scheduled date.
 */
class SavingsPlan extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE    = 'active';
    const STATUS_PAUSED    = 'paused';
    const STATUS_CANCELLED = 'cancelled';

    const FREQUENCY_MONTHLY    = 'monthly';
    const FREQUENCY_BIWEEKLY   = 'biweekly';
    const FREQUENCY_WEEKLY     = 'weekly';

    protected $fillable = [
        'user_id',
        'asset_id',
        'amount_per_cycle', // EUR/USD amount to invest each cycle
        'currency',
        'frequency',
        'execution_day',    // 1–28 for monthly plans
        'status',
        'total_invested',
        'total_quantity',   // total troy oz / grams accumulated
        'last_executed_at',
        'next_execution_at',
        'metadata',
    ];

    protected $casts = [
        'amount_per_cycle'  => 'decimal:2',
        'total_invested'    => 'decimal:2',
        'total_quantity'    => 'decimal:6',
        'last_executed_at'  => 'datetime',
        'next_execution_at' => 'datetime',
        'metadata'          => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Order::class, 'metadata->savings_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDueForExecution($query)
    {
        return $query->active()->where('next_execution_at', '<=', now());
    }

    public function averageCostBasis(): float
    {
        if ($this->total_quantity == 0) {
            return 0.0;
        }

        return (float) ($this->total_invested / $this->total_quantity);
    }
}
