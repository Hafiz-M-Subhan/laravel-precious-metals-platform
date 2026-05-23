<?php

namespace App\Jobs;

use App\Events\MarketSummaryUpdated;
use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastMarketSummary implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 25; // seconds — prevents overlap with 30s schedule

    public function handle(): void
    {
        $assets = Asset::active()->get();

        if ($assets->isEmpty()) {
            return;
        }

        $gainers   = $assets->where('daily_change_pct', '>', 0);
        $losers    = $assets->where('daily_change_pct', '<', 0);
        $topGainer = $assets->sortByDesc('daily_change_pct')->first();
        $topLoser  = $assets->sortBy('daily_change_pct')->first();

        MarketSummaryUpdated::dispatch([
            'total_assets'      => $assets->count(),
            'gainers'           => $gainers->count(),
            'losers'            => $losers->count(),
            'unchanged'         => $assets->count() - $gainers->count() - $losers->count(),
            'top_gainer_symbol' => $topGainer?->symbol,
            'top_gainer_pct'    => $topGainer ? (float) $topGainer->daily_change_pct : 0.0,
            'top_loser_symbol'  => $topLoser?->symbol,
            'top_loser_pct'     => $topLoser ? (float) $topLoser->daily_change_pct : 0.0,
            'timestamp'         => now()->toIso8601String(),
        ]);
    }
}
