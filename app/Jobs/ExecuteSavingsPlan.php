<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SavingsPlanExecuted;
use App\Models\SavingsPlan;
use App\Services\SavingsPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Executes a single savings plan cycle — buys the configured amount
 * at the current spot price and schedules the next execution.
 *
 * Dispatched by the ScheduleSavingsPlans artisan command (runs every minute).
 */
class ExecuteSavingsPlan implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly SavingsPlan $plan) {}

    public function uniqueId(): string
    {
        return "savings_plan_{$this->plan->id}_{$this->plan->next_execution_at->format('Y-m-d')}";
    }

    public function handle(SavingsPlanService $service): void
    {
        if ($this->plan->status !== SavingsPlan::STATUS_ACTIVE) {
            return;
        }

        [$updatedPlan, $order] = $service->execute($this->plan);

        broadcast(new SavingsPlanExecuted($updatedPlan, $order));

        Log::info('Savings plan executed', [
            'plan_id'   => $this->plan->id,
            'user_id'   => $this->plan->user_id,
            'amount'    => $order->total_amount,
            'next_run'  => $updatedPlan->next_execution_at,
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Savings plan execution failed', [
            'plan_id' => $this->plan->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
