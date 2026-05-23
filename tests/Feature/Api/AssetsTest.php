<?php

use App\Models\Asset;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a list of active assets without authentication', function () {
    Asset::factory()->count(3)->create(['is_active' => true]);
    Asset::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/v1/assets');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'symbol', 'name', 'spot_price', 'bid_price', 'ask_price']]])
        ->assertJsonCount(3, 'data');
});

it('returns a single asset by symbol without authentication', function () {
    Asset::factory()->create(['symbol' => 'XAU', 'is_active' => true]);

    $response = $this->getJson('/api/v1/assets/XAU');

    $response->assertOk()
        ->assertJsonPath('data.symbol', 'XAU');
});

it('returns 404 for unknown asset symbol', function () {
    $this->getJson('/api/v1/assets/UNKNOWN')->assertNotFound();
});

it('returns candles for an asset', function () {
    $asset = Asset::factory()->create(['symbol' => 'XPT', 'is_active' => true]);

    $response = $this->getJson("/api/v1/assets/XPT/candles?resolution=1m&from=" . now()->subHour()->toDateTimeString() . "&to=" . now()->toDateTimeString());

    $response->assertOk()
        ->assertJsonStructure(['data']);
});
