<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the authenticated user's private channel when their order
 * transitions to "filled". The frontend dismisses the pending spinner and
 * shows a success toast without polling.
 *
 * Channel: private-user.{user_id}
 */
class OrderFulfilled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->order->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.fulfilled';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'     => $this->order->id,
            'asset_symbol' => $this->order->asset->symbol,
            'side'         => $this->order->side,
            'quantity'     => (float) $this->order->quantity,
            'price'        => (float) $this->order->price_per_unit,
            'total'        => (float) $this->order->total_amount,
            'currency'     => $this->order->currency,
            'filled_at'    => $this->order->filled_at->toIso8601String(),
        ];
    }
}
