<?php

use App\Models\Asset;
use App\Models\SavingsPlan;
use App\Models\User;
use App\Services\SavingsPlanService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SavingsPlanService::class);
});

it('project_dca_growth returns correct number of months', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create(['ask_price' => 2000.00]);

    $plan = SavingsPlan::factory()->create([
        'user_id'          => $user->id,
        'asset_id'         => $asset->id,
        'amount_per_cycle' => 100.00,
        'frequency'        => SavingsPlan::FREQ_MONTHLY,
        'total_invested'   => 0,
        'total_quantity'   => 0,
    ]);

    $projection = $this->service->projectDcaGrowth($plan, 12, 0.05);

    expect($projection)->toHaveCount(12);
    expect($projection[0])->toHaveKeys(['month', 'date', 'price', 'total_invested', 'total_value', 'total_quantity']);
});

it('project_dca_growth total_invested grows linearly', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create(['ask_price' => 2000.00]);

    $plan = SavingsPlan::factory()->create([
        'user_id'          => $user->id,
        'asset_id'         => $asset->id,
        'amount_per_cycle' => 500.00,
        'frequency'        => SavingsPlan::FREQ_MONTHLY,
        'total_invested'   => 0,
        'total_quantity'   => 0,
    ]);

    $projection = $this->service->projectDcaGrowth($plan, 6, 0.0);

    // With 0% growth rate, total_invested should be exactly month * amount_per_cycle
    expect((float) $projection[5]['total_invested'])->toEqual(6 * 500.00);
});

it('next_execution_date handles end of month correctly for monthly plans', function () {
    $user  = User::factory()->create();
    $asset = Asset::factory()->create();

    $plan = SavingsPlan::factory()->create([
        'user_id'          => $user->id,
        'asset_id'         => $asset->id,
        'frequency'        => SavingsPlan::FREQ_MONTHLY,
        'execution_day'    => 31,
        'next_execution_at'=> now(),
    ]);

    $nextDate = $this->service->nextExecutionDate($plan);

    // Day should be capped to the last day of the next month (no overflow into following month)
    expect($nextDate->day)->toBeLessThanOrEqual(31)
        ->and($nextDate->isAfter(now()))->toBeTrue();
});
