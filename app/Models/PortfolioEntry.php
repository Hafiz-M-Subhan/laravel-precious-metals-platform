<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'asset_id',
        'quantity',
        'avg_cost_basis',
        'total_invested',
        'realized_pnl',
    ];

    protected $casts = [
        'quantity'       => 'decimal:6',
        'avg_cost_basis' => 'decimal:6',
        'total_invested' => 'decimal:2',
        'realized_pnl'   => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function unrealizedPnl(float $currentPrice): float
    {
        return ($currentPrice - (float) $this->avg_cost_basis) * (float) $this->quantity;
    }

    public function unrealizedPnlPct(float $currentPrice): float
    {
        if ((float) $this->avg_cost_basis === 0.0) {
            return 0.0;
        }

        return (($currentPrice - (float) $this->avg_cost_basis) / (float) $this->avg_cost_basis) * 100;
    }

    public function currentValue(float $currentPrice): float
    {
        return (float) $this->quantity * $currentPrice;
    }
}
