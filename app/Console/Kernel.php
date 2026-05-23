<?php

namespace App\Console;

use App\Jobs\BroadcastMarketSummary;
use App\Jobs\CheckPriceAlerts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // DCA savings plans — dispatch ExecuteSavingsPlan for any due plans
        $schedule->command('savings-plans:schedule')
            ->everyMinute()
            ->name('schedule-savings-plans')
            ->withoutOverlapping();

        // Price alerts — check every minute against Redis-cached prices
        $schedule->job(new CheckPriceAlerts, 'default')
            ->everyMinute()
            ->name('check-price-alerts')
            ->withoutOverlapping();

        // Market summary broadcast — pushes gainers/losers to market-summary WebSocket channel
        $schedule->job(new BroadcastMarketSummary, 'default')
            ->everyThirtySeconds()
            ->name('broadcast-market-summary')
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
