<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\SavingsPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SavingsPlanService
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * Execute one savings plan cycle:
     *  1. Calculate quantity from amount ÷ current ask price
     *  2. Place + fill a market buy order
     *  3. Update totals and schedule next run
     *
     * Returns [$updatedPlan, $filledOrder].
     */
    public function execute(SavingsPlan $plan): array
    {
        return DB::transaction(function () use ($plan) {
            $asset    = $plan->asset;
            $askPrice = (float) $asset->ask_price;

            if ($askPrice <= 0) {
                throw new \RuntimeException("Asset {$asset->symbol} has no valid ask price.");
            }

            $quantity = (float) $plan->amount_per_cycle / $askPrice;

            $order = Order::create([
                'user_id'        => $plan->user_id,
                'asset_id'       => $plan->asset_id,
                'side'           => Order::SIDE_BUY,
                'type'           => 'market',
                'quantity'       => $quantity,
                'price_per_unit' => $askPrice,
                'total_amount'   => $plan->amount_per_cycle,
                'currency'       => $plan->currency,
                'status'         => Order::STATUS_FILLED,
                'filled_at'      => now(),
                'metadata'       => ['savings_plan_id' => $plan->id],
            ]);

            $plan->increment('total_invested', $plan->amount_per_cycle);
            $plan->increment('total_quantity', $quantity);

            $plan->update([
                'last_executed_at'  => now(),
                'next_execution_at' => $this->nextExecutionDate($plan),
            ]);

            return [$plan->fresh(), $order];
        });
    }

    /**
     * Calculate the next execution date based on frequency and execution_day.
     */
    public function nextExecutionDate(SavingsPlan $plan): Carbon
    {
        $base = now();

        return match ($plan->frequency) {
            SavingsPlan::FREQUENCY_WEEKLY   => $base->addWeek(),
            SavingsPlan::FREQUENCY_BIWEEKLY => $base->addWeeks(2),
            default => $base->addMonthNoOverflow()->setDay(
                min($plan->execution_day, $base->addMonthNoOverflow()->daysInMonth)
            ),
        };
    }

    /**
     * Project future portfolio value using Dollar-Cost Averaging math.
     * Used by the frontend chart to show projected growth.
     *
     * @param  int    $months      Number of months to project
     * @param  float  $annualGrowth  Assumed annual price growth rate (e.g. 0.05 = 5%)
     */
    public function projectDcaGrowth(SavingsPlan $plan, int $months = 12, float $annualGrowth = 0.05): array
    {
        $monthlyGrowth = (1 + $annualGrowth) ** (1 / 12) - 1;
        $currentPrice  = (float) $plan->asset->spot_price;
        $monthlyAmount = (float) $plan->amount_per_cycle;

        $projections = [];
        $totalInvested  = (float) $plan->total_invested;
        $totalQuantity  = (float) $plan->total_quantity;

        for ($i = 1; $i <= $months; $i++) {
            $currentPrice  *= (1 + $monthlyGrowth);
            $newQty        = $monthlyAmount / $currentPrice;
            $totalQuantity += $newQty;
            $totalInvested += $monthlyAmount;

            $projections[] = [
                'month'          => $i,
                'date'           => now()->addMonths($i)->format('Y-m'),
                'price'          => round($currentPrice, 2),
                'total_invested' => round($totalInvested, 2),
                'total_value'    => round($totalQuantity * $currentPrice, 2),
                'total_quantity' => round($totalQuantity, 6),
            ];
        }

        return $projections;
    }
}
