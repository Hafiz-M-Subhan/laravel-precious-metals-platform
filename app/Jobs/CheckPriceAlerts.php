<?php

namespace App\Jobs;

use App\Events\PriceAlertFired;
use App\Models\PriceAlert;
use App\Notifications\PriceAlertTriggered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckPriceAlerts implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 55; // seconds — prevents overlap with next minute's dispatch

    public function handle(): void
    {
        PriceAlert::active()
            ->with('asset', 'user')
            ->lazyById(100)
            ->each(function (PriceAlert $alert): void {
                // Read from Redis price cache (5s TTL) for accuracy; fallback to DB column
                $currentPrice = (float) Cache::get(
                    "price:{$alert->asset->symbol}",
                    $alert->asset->spot_price
                );

                if (! $alert->isTriggered($currentPrice)) {
                    return;
                }

                $alert->update([
                    'is_active'    => false,
                    'triggered_at' => now(),
                ]);

                $alert->user->notify(new PriceAlertTriggered($alert, $currentPrice));

                PriceAlertFired::dispatch($alert, $currentPrice);

                Log::info('Price alert triggered', [
                    'alert_id'       => $alert->id,
                    'user_id'        => $alert->user_id,
                    'symbol'         => $alert->asset->symbol,
                    'condition'      => $alert->condition,
                    'target'         => (float) $alert->target_price,
                    'triggered_price'=> $currentPrice,
                ]);
            });
    }
}
