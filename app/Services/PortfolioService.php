<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Portfolio;
use App\Models\PortfolioEntry;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function applyOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $entry = PortfolioEntry::firstOrCreate(
                ['user_id' => $order->user_id, 'asset_id' => $order->asset_id],
                ['quantity' => 0, 'avg_cost_basis' => 0, 'total_invested' => 0, 'realized_pnl' => 0]
            );

            if ($order->side === Order::SIDE_BUY) {
                $oldQty   = (float) $entry->quantity;
                $newQty   = (float) $order->quantity;
                $totalQty = $oldQty + $newQty;

                // Weighted average cost basis
                $entry->avg_cost_basis = $totalQty > 0
                    ? (($oldQty * (float) $entry->avg_cost_basis) + ($newQty * (float) $order->price_per_unit)) / $totalQty
                    : (float) $order->price_per_unit;

                $entry->quantity       = $totalQty;
                $entry->total_invested = (float) $entry->total_invested + (float) $order->total_amount;
            } else {
                // Sell: realize P&L = (sale_price - cost_basis) * quantity
                $realizedPnl = ((float) $order->price_per_unit - (float) $entry->avg_cost_basis) * (float) $order->quantity;

                $entry->realized_pnl = (float) $entry->realized_pnl + $realizedPnl;
                $entry->quantity     = max(0.0, (float) $entry->quantity - (float) $order->quantity);
            }

            $entry->save();

            $this->refreshPortfolio($order->user_id);
        });
    }

    public function refreshPortfolio(int $userId): Portfolio
    {
        $portfolio = Portfolio::firstOrCreate(
            ['user_id' => $userId],
            ['total_value' => 0, 'total_cost' => 0, 'unrealized_pnl' => 0, 'realized_pnl' => 0, 'currency' => 'USD']
        );

        $entries = PortfolioEntry::where('user_id', $userId)->with('asset')->get();

        $totalValue    = 0.0;
        $totalCost     = 0.0;
        $unrealizedPnl = 0.0;
        $realizedPnl   = 0.0;

        foreach ($entries as $entry) {
            $currentPrice   = (float) $entry->asset->spot_price;
            $qty            = (float) $entry->quantity;
            $totalValue    += $currentPrice * $qty;
            $totalCost     += (float) $entry->total_invested;
            $unrealizedPnl += $entry->unrealizedPnl($currentPrice);
            $realizedPnl   += (float) $entry->realized_pnl;
        }

        $portfolio->update([
            'total_value'    => $totalValue,
            'total_cost'     => $totalCost,
            'unrealized_pnl' => $unrealizedPnl,
            'realized_pnl'   => $realizedPnl,
        ]);

        return $portfolio->fresh();
    }

    public function getSummary(int $userId): array
    {
        $portfolio = Portfolio::firstOrCreate(
            ['user_id' => $userId],
            ['total_value' => 0, 'total_cost' => 0, 'unrealized_pnl' => 0, 'realized_pnl' => 0, 'currency' => 'USD']
        );

        $holdings = PortfolioEntry::where('user_id', $userId)
            ->where('quantity', '>', 0)
            ->with('asset')
            ->get()
            ->map(function (PortfolioEntry $entry) use ($portfolio): array {
                $currentPrice   = (float) $entry->asset->spot_price;
                $currentValue   = $entry->currentValue($currentPrice);
                $allocationPct  = $portfolio->total_value > 0
                    ? ($currentValue / (float) $portfolio->total_value) * 100
                    : 0;

                return [
                    'asset'               => $entry->asset->symbol,
                    'name'                => $entry->asset->name,
                    'quantity'            => (float) $entry->quantity,
                    'avg_cost_basis'      => (float) $entry->avg_cost_basis,
                    'current_price'       => $currentPrice,
                    'current_value'       => $currentValue,
                    'total_invested'      => (float) $entry->total_invested,
                    'unrealized_pnl'      => $entry->unrealizedPnl($currentPrice),
                    'unrealized_pnl_pct'  => round($entry->unrealizedPnlPct($currentPrice), 2),
                    'realized_pnl'        => (float) $entry->realized_pnl,
                    'allocation_pct'      => round($allocationPct, 2),
                ];
            });

        return [
            'total_value'    => (float) $portfolio->total_value,
            'total_cost'     => (float) $portfolio->total_cost,
            'unrealized_pnl' => (float) $portfolio->unrealized_pnl,
            'realized_pnl'   => (float) $portfolio->realized_pnl,
            'pnl_percentage' => $portfolio->pnlPercentage(),
            'currency'       => $portfolio->currency,
            'holdings'       => $holdings,
        ];
    }
}
