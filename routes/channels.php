<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| prices.{symbol}    — Public channel. Any visitor can subscribe.
| live-event         — Presence channel. Viewer count shown on the live page.
| user.{id}          — Private channel. Only the owner can subscribe.
|
*/

// Public price channels — no authorization needed (unauthenticated viewers OK)
Broadcast::channel('prices.{symbol}', fn () => true);

// Presence channel for the live-event page (returns user metadata for viewer count)
Broadcast::channel('live-event', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

// Private per-user channel for order fills and savings plan notifications
Broadcast::channel('user.{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId;
});
