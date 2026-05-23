<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Portfolio;
use App\Models\PortfolioEntry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(private readonly PortfolioService $portfolioService) {}

    /**
     * Create and immediately queue a market order.
     * The actual fill happens asynchronously in ProcessOrder.
     */
    public function placeMarket(int $userId, int $assetId, string $side, float $quantity): Order
    {
        $asset = Asset::active()->findOrFail($assetId);

        $pricePerUnit = $side === Order::SIDE_BUY
            ? (float) $asset->ask_price
            : (float) $asset->bid_price;

        $order = Order::create([
            'user_id'       => $userId,
            'asset_id'      => $assetId,
            'side'          => $side,
            'type'          => 'market',
            'quantity'      => $quantity,
            'price_per_unit' => $pricePerUnit,
            'total_amount'  => $pricePerUnit * $quantity,
            'currency'      => $asset->currency,
            'status'        => Order::STATUS_PENDING,
        ]);

        \App\Jobs\ProcessOrder::dispatch($order)->onQueue('orders');

        return $order;
    }

    /**
     * Fill a pending order — called inside ProcessOrder job.
     * Wrapped in a transaction so the portfolio update and order fill are atomic.
     */
    public function fill(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $asset = Asset::lockForUpdate()->find($order->asset_id);

            // Re-fetch the live price at fill time (slip protection)
            $fillPrice = $order->side === Order::SIDE_BUY
                ? (float) $asset->ask_price
                : (float) $asset->bid_price;

            $order->update([
                'status'        => Order::STATUS_FILLED,
                'price_per_unit' => $fillPrice,
                'total_amount'  => $fillPrice * $order->quantity,
                'filled_at'     => now(),
            ]);

            $this->portfolioService->applyOrder($order);

            return $order->fresh();
        });
    }

    /**
     * Cancel a pending order. Throws if already processed.
     */
    public function cancel(Order $order): Order
    {
        if (! $order->isPending()) {
            throw new RuntimeException("Cannot cancel order in status: {$order->status}");
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return $order;
    }
}
