<?php

namespace App\Events;

use App\Models\PriceAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceAlertFired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PriceAlert $alert,
        public readonly float $triggeredPrice
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->alert->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'price_alert.fired';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id'        => $this->alert->id,
            'asset_symbol'    => $this->alert->asset->symbol,
            'asset_name'      => $this->alert->asset->name,
            'condition'       => $this->alert->condition,
            'target_price'    => (float) $this->alert->target_price,
            'triggered_price' => $this->triggeredPrice,
            'triggered_at'    => now()->toIso8601String(),
        ];
    }
}
