<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Asset;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to all connected clients whenever a metal's spot price changes.
 * Uses a public channel so unauthenticated visitors on the live-event page
 * also receive real-time ticks without a WebSocket handshake round-trip.
 *
 * Channel: prices.{symbol}  (e.g. prices.XAU)
 * Presence channel: live-event  (carries viewer count for the live event page)
 */
class PriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly float $previousPrice,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("prices.{$this->asset->symbol}"),
            new PresenceChannel('live-event'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'price.updated';
    }

    /**
     * Only send a subset of fields to keep the payload tiny —
     * every byte matters at 30,000 concurrent subscribers.
     */
    public function broadcastWith(): array
    {
        return [
            'symbol'           => $this->asset->symbol,
            'spot'             => (float) $this->asset->spot_price,
            'bid'              => (float) $this->asset->bid_price,
            'ask'              => (float) $this->asset->ask_price,
            'change'           => (float) $this->asset->daily_change,
            'change_pct'       => (float) $this->asset->daily_change_pct,
            'previous'         => $this->previousPrice,
            'direction'        => $this->asset->spot_price > $this->previousPrice ? 'up' : 'down',
            'ts'               => now()->toIso8601String(),
        ];
    }

    /**
     * Skip broadcasting if the price hasn't meaningfully changed
     * (< 0.001% movement) to avoid flooding the queue.
     */
    public function broadcastWhen(): bool
    {
        if ($this->previousPrice == 0) {
            return true;
        }

        $changePct = abs(($this->asset->spot_price - $this->previousPrice) / $this->previousPrice);

        return $changePct >= 0.00001;
    }
}
