<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OHLCV candle record. Indexed on (asset_id, resolution, recorded_at)
 * for fast chart queries. Partitioned by month on large deployments.
 */
class PriceHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'resolution',   // 1m | 5m | 15m | 1h | 1d
        'open',
        'high',
        'low',
        'close',
        'volume',
        'recorded_at',
    ];

    protected $casts = [
        'open'        => 'decimal:6',
        'high'        => 'decimal:6',
        'low'         => 'decimal:6',
        'close'       => 'decimal:6',
        'volume'      => 'decimal:4',
        'recorded_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeResolution(Builder $query, string $resolution): Builder
    {
        return $query->where('resolution', $resolution);
    }

    public function scopeRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }
}
