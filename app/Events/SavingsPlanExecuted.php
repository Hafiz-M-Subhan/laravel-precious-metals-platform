<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use App\Models\SavingsPlan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SavingsPlanExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SavingsPlan $plan,
        public readonly Order $order,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->plan->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'savings_plan.executed';
    }

    public function broadcastWith(): array
    {
        return [
            'plan_id'          => $this->plan->id,
            'asset_symbol'     => $this->plan->asset->symbol,
            'amount_invested'  => (float) $this->order->total_amount,
            'quantity_bought'  => (float) $this->order->quantity,
            'price_per_unit'   => (float) $this->order->price_per_unit,
            'total_invested'   => (float) $this->plan->total_invested,
            'total_quantity'   => (float) $this->plan->total_quantity,
            'avg_cost_basis'   => $this->plan->averageCostBasis(),
            'next_execution'   => $this->plan->next_execution_at->toIso8601String(),
        ];
    }
}
