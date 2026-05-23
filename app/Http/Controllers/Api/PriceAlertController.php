<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePriceAlertRequest;
use App\Http\Resources\PriceAlertResource;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = PriceAlert::where('user_id', $request->user()->id)
            ->with('asset')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => PriceAlertResource::collection($alerts->items()),
            'meta' => ['total' => $alerts->total(), 'per_page' => $alerts->perPage()],
        ]);
    }

    public function store(CreatePriceAlertRequest $request): JsonResponse
    {
        $alert = PriceAlert::create([
            'user_id'      => $request->user()->id,
            'asset_id'     => $request->input('asset_id'),
            'condition'    => $request->input('condition'),
            'target_price' => $request->input('target_price'),
            'note'         => $request->input('note'),
            'is_active'    => true,
        ]);

        $alert->load('asset');

        return response()->json(['data' => new PriceAlertResource($alert)], 201);
    }

    public function destroy(Request $request, PriceAlert $priceAlert): JsonResponse
    {
        $this->authorize('delete', $priceAlert);

        $priceAlert->delete();

        return response()->json(['message' => 'Price alert deleted.']);
    }
}
