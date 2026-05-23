<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketSummaryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly array $summary) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('market-summary'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'market.summary';
    }

    public function broadcastWith(): array
    {
        return $this->summary;
    }
}
