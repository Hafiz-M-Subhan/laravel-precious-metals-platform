<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSavingsPlanRequest;
use App\Http\Resources\SavingsPlanResource;
use App\Models\SavingsPlan;
use App\Services\SavingsPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SavingsPlanController extends Controller
{
    public function __construct(private readonly SavingsPlanService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $plans = SavingsPlan::where('user_id', auth()->id())
            ->with('asset:id,symbol,name,unit')
            ->get();

        return SavingsPlanResource::collection($plans);
    }

    public function store(CreateSavingsPlanRequest $request): JsonResponse
    {
        $plan = SavingsPlan::create([
            'user_id'           => auth()->id(),
            'asset_id'          => $request->asset_id,
            'amount_per_cycle'  => $request->amount,
            'currency'          => $request->currency ?? 'EUR',
            'frequency'         => $request->frequency,
            'execution_day'     => $request->execution_day ?? 1,
            'status'            => SavingsPlan::STATUS_ACTIVE,
            'total_invested'    => 0,
            'total_quantity'    => 0,
            'next_execution_at' => $this->service->nextExecutionDate(new SavingsPlan($request->validated())),
        ]);

        return response()->json(new SavingsPlanResource($plan->load('asset')), 201);
    }

    public function show(SavingsPlan $savingsPlan): SavingsPlanResource
    {
        $this->authorize('view', $savingsPlan);

        return new SavingsPlanResource($savingsPlan->load('asset'));
    }

    /**
     * GET /api/v1/savings-plans/{id}/projection
     *
     * Returns a DCA growth projection for the frontend chart.
     * ?months=24&annual_growth=0.07
     */
    public function projection(SavingsPlan $savingsPlan): JsonResponse
    {
        $this->authorize('view', $savingsPlan);

        $months      = (int) request('months', 12);
        $annualGrowth = (float) request('annual_growth', 0.05);

        $projection = $this->service->projectDcaGrowth($savingsPlan->load('asset'), $months, $annualGrowth);

        return response()->json([
            'plan_id'    => $savingsPlan->id,
            'asset'      => $savingsPlan->asset->symbol,
            'projection' => $projection,
        ]);
    }

    public function destroy(SavingsPlan $savingsPlan): JsonResponse
    {
        $this->authorize('delete', $savingsPlan);

        $savingsPlan->update(['status' => SavingsPlan::STATUS_CANCELLED]);

        return response()->json(['message' => 'Savings plan cancelled.']);
    }
}
