<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\PriceUpdated;
use App\Models\Asset;
use App\Services\PriceService;
use Illuminate\Console\Command;

/**
 * php artisan prices:simulate
 *
 * Simulates a real-time precious metals price feed for local development.
 * In production this is replaced by a WebSocket feed from a metals data provider
 * (e.g. Metals-API, Refinitiv) consumed by PriceService::ingestTick().
 */
class SimulatePriceFeed extends Command
{
    protected $signature = 'prices:simulate
                            {--interval=2 : Seconds between ticks}
                            {--volatility=0.002 : Price volatility per tick (0.002 = 0.2%)}';

    protected $description = 'Simulate live precious-metals price ticks and broadcast via WebSocket (dev only)';

    public function handle(PriceService $priceService): void
    {
        $interval   = (int) $this->option('interval');
        $volatility = (float) $this->option('volatility');

        $this->info("Simulating price feed (interval={$interval}s, volatility={$volatility})…");
        $this->info('Press Ctrl+C to stop.');

        $assets = Asset::active()->get();

        while (true) {
            foreach ($assets as $asset) {
                $previousPrice = (float) $asset->spot_price;
                $newSpot       = $priceService->simulateTick($asset, $volatility);

                broadcast(new PriceUpdated($asset->refresh(), $previousPrice));

                $this->line(sprintf(
                    '[%s] %s %s → %s  (%+.4f%%)',
                    now()->format('H:i:s'),
                    $asset->symbol,
                    number_format($previousPrice, 2),
                    number_format($newSpot, 2),
                    (($newSpot - $previousPrice) / $previousPrice) * 100,
                ));
            }

            sleep($interval);
        }
    }
}
