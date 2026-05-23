<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_value',    // Current market value (recalculated on each price tick)
        'total_cost',     // Historical cost basis
        'unrealized_pnl',
        'realized_pnl',
        'currency',
    ];

    protected $casts = [
        'total_value'    => 'decimal:2',
        'total_cost'     => 'decimal:2',
        'unrealized_pnl' => 'decimal:2',
        'realized_pnl'   => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PortfolioEntry::class);
    }

    public function pnlPercentage(): float
    {
        if ($this->total_cost == 0) {
            return 0.0;
        }

        return (float) (($this->unrealized_pnl / $this->total_cost) * 100);
    }
}
