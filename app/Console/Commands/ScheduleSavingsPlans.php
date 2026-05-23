<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ExecuteSavingsPlan;
use App\Models\SavingsPlan;
use Illuminate\Console\Command;

/**
 * php artisan savings-plans:schedule
 *
 * Runs every minute via the scheduler. Finds all active plans whose
 * next_execution_at is due and dispatches an ExecuteSavingsPlan job for each.
 * Uses chunk() to avoid loading thousands of plans into memory at once.
 */
class ScheduleSavingsPlans extends Command
{
    protected $signature = 'savings-plans:schedule';
    protected $description = 'Dispatch execution jobs for all due savings plans';

    public function handle(): void
    {
        $dispatched = 0;

        SavingsPlan::dueForExecution()
            ->with('asset')
            ->chunkById(200, function ($plans) use (&$dispatched) {
                foreach ($plans as $plan) {
                    ExecuteSavingsPlan::dispatch($plan)->onQueue('savings-plans');
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} savings plan job(s).");
    }
}
