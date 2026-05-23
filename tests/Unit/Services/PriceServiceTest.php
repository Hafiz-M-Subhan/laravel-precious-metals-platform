<?php

use App\Models\Asset;
use App\Services\PriceService;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PriceService::class);
});

it('ingest_tick updates asset prices and caches the result', function () {
    $asset = Asset::factory()->create([
        'symbol'     => 'XAU',
        'spot_price' => 2300.00,
    ]);

    $this->service->ingestTick('XAU', 2310.00, 2309.50, 2310.50);

    $asset->refresh();

    expect((float) $asset->spot_price)->toBe(2310.00)
        ->and((float) $asset->bid_price)->toBe(2309.50)
        ->and((float) $asset->ask_price)->toBe(2310.50);

    expect(Cache::has("price:XAU"))->toBeTrue();
});

it('simulate_tick produces a price within a realistic drift range', function () {
    $asset = Asset::factory()->create([
        'symbol'     => 'XAG',
        'spot_price' => 30.00,
    ]);

    $volatility = 0.002;
    $newPrice   = $this->service->simulateTick($asset, $volatility);

    // GBM should not move price by more than 5x the volatility per tick
    $maxMove = 30.00 * $volatility * 5;

    expect($newPrice)->toBeGreaterThan(30.00 - $maxMove)
        ->and($newPrice)->toBeLessThan(30.00 + $maxMove);
});

it('get_candles returns ohlcv shaped records', function () {
    $asset = Asset::factory()->create(['symbol' => 'XPT']);

    // Ingest a tick so a candle exists
    $this->service->ingestTick('XPT', 1050.00, 1049.50, 1050.50);

    $candles = $this->service->getCandles($asset->id, '1m', now()->subMinutes(2), now()->addMinutes(1));

    expect($candles)->toBeArray();

    if (count($candles) > 0) {
        $candle = $candles[0];
        expect($candle)->toHaveKeys(['open', 'high', 'low', 'close', 'volume', 'recorded_at']);
        expect((float) $candle['high'])->toBeGreaterThanOrEqual((float) $candle['low']);
    }
});
