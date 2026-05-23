<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| prices.{symbol}    — Public.   Any visitor subscribes for live tick data.
| market-summary     — Public.   Gainers/losers broadcast every 30 seconds.
| live-event         — Presence. Viewer count shown on the live dashboard.
| user.{id}          — Private.  Order fills, DCA executions, price alerts.
|
*/

// Public price channels — no authorization (unauthenticated live-event viewers OK)
Broadcast::channel('prices.{symbol}', fn () => true);

// Public market summary channel
Broadcast::channel('market-summary', fn () => true);

// Presence channel: returns user metadata so the viewer count reflects real users
Broadcast::channel('live-event', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

// Private per-user channel: order fills, DCA executions, price alert fires
Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});
