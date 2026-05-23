<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PriceService
{
    private const CACHE_TTL = 5; // seconds — price data is highly volatile

    /**
     * Ingest a real-time price tick from an external feed (Metals-API, Refinitiv, etc.)
     * Updates the asset row and appends a 1m OHLCV candle in a single transaction.
     */
    public function ingestTick(string $symbol, float $spot, float $bid, float $ask): Asset
    {
        return DB::transaction(function () use ($symbol, $spot, $bid, $ask) {
            $asset = Asset::where('symbol', $symbol)->lockForUpdate()->firstOrFail();

            $previousSpot = (float) $asset->spot_price;
            $dailyOpen    = $this->getDailyOpen($asset->id) ?? $spot;
            $dailyChange  = $spot - $dailyOpen;

            $asset->update([
                'spot_price'       => $spot,
                'bid_price'        => $bid,
                'ask_price'        => $ask,
                'spread'           => $ask - $bid,
                'daily_change'     => $dailyChange,
                'daily_change_pct' => $dailyOpen > 0 ? ($dailyChange / $dailyOpen) * 100 : 0,
            ]);

            $this->upsertCandle($asset->id, $spot, '1m');

            Cache::put("asset:price:{$symbol}", [
                'spot' => $spot, 'bid' => $bid, 'ask' => $ask,
                'ts'   => now()->toIso8601String(),
            ], self::CACHE_TTL);

            return $asset;
        });
    }

    /**
     * Simulate a single price tick using geometric Brownian motion (dev only).
     * Returns the new spot price.
     */
    public function simulateTick(Asset $asset, float $volatility = 0.002): float
    {
        $current = (float) $asset->spot_price;
        // GBM: S(t+dt) = S(t) * exp((μ - σ²/2)dt + σ * W)
        $drift   = 0.00001;
        $shock   = $volatility * (lcg_value() * 2 - 1);
        $newSpot = $current * exp($drift + $shock);

        $spread = $newSpot * 0.001;

        $this->ingestTick($asset->symbol, $newSpot, $newSpot - $spread / 2, $newSpot + $spread / 2);

        return $newSpot;
    }

    /**
     * Returns cached OHLCV candles for a given asset and resolution.
     * Cache key encodes asset + resolution + date range so we can invalidate per-asset.
     */
    public function getCandles(int $assetId, string $resolution, string $from, string $to): array
    {
        $key = "candles:{$assetId}:{$resolution}:{$from}:{$to}";

        return Cache::remember($key, 60, function () use ($assetId, $resolution, $from, $to) {
            return PriceHistory::where('asset_id', $assetId)
                ->resolution($resolution)
                ->range($from, $to)
                ->orderBy('recorded_at')
                ->get(['open', 'high', 'low', 'close', 'volume', 'recorded_at'])
                ->toArray();
        });
    }

    /**
     * Upsert the current 1-minute candle.
     * On the first tick within a minute, opens a new candle.
     * Subsequent ticks update high/low/close in-place.
     */
    private function upsertCandle(int $assetId, float $price, string $resolution): void
    {
        $bucketStart = now()->startOfMinute();

        PriceHistory::upsert(
            [[
                'asset_id'    => $assetId,
                'resolution'  => $resolution,
                'open'        => $price,
                'high'        => $price,
                'low'         => $price,
                'close'       => $price,
                'volume'      => 0,
                'recorded_at' => $bucketStart,
            ]],
            uniqueBy: ['asset_id', 'resolution', 'recorded_at'],
            update: [
                'high'  => DB::raw("GREATEST(high, {$price})"),
                'low'   => DB::raw("LEAST(low, {$price})"),
                'close' => $price,
            ],
        );
    }

    private function getDailyOpen(int $assetId): ?float
    {
        return Cache::remember("daily_open:{$assetId}", 3600, function () use ($assetId) {
            return PriceHistory::where('asset_id', $assetId)
                ->resolution('1d')
                ->where('recorded_at', '>=', today())
                ->value('open');
        });
    }
}
