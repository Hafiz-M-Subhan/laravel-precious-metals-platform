<?php

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('rejects unauthenticated order placement', function () {
    $this->postJson('/api/v1/orders', [])->assertUnauthorized();
});

it('places a market order and returns 202 accepted', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create(['is_active' => true, 'ask_price' => 2000.00]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'asset_id' => $asset->id,
            'side'     => 'buy',
            'quantity' => 0.5,
        ]);

    $response->assertAccepted()
        ->assertJsonPath('data.status', Order::STATUS_PENDING)
        ->assertJsonPath('data.side', 'buy');
});

it('rejects order with invalid quantity', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create(['is_active' => true]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/orders', [
            'asset_id' => $asset->id,
            'side'     => 'buy',
            'quantity' => 0.0001, // below min:0.001
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

it('cancels a pending order', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create(['is_active' => true]);

    $order = Order::factory()->create([
        'user_id'  => $user->id,
        'asset_id' => $asset->id,
        'status'   => Order::STATUS_PENDING,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/orders/{$order->id}")
        ->assertOk();

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});

it('forbids cancelling another users order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $asset = Asset::factory()->create();

    $order = Order::factory()->create([
        'user_id'  => $user2->id,
        'asset_id' => $asset->id,
        'status'   => Order::STATUS_PENDING,
    ]);

    $this->actingAs($user1, 'sanctum')
        ->deleteJson("/api/v1/orders/{$order->id}")
        ->assertForbidden();
});
