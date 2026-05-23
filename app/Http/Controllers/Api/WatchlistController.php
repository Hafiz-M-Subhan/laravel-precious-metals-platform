<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WatchlistResource;
use App\Models\Asset;
use App\Models\Watchlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Watchlist::where('user_id', $request->user()->id)
            ->with('asset')
            ->latest()
            ->get();

        return response()->json(['data' => WatchlistResource::collection($items)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
        ]);

        $item = Watchlist::firstOrCreate([
            'user_id'  => $request->user()->id,
            'asset_id' => $request->input('asset_id'),
        ]);

        $item->load('asset');

        return response()->json(['data' => new WatchlistResource($item)], 201);
    }

    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        Watchlist::where('user_id', $request->user()->id)
            ->where('asset_id', $asset->id)
            ->delete();

        return response()->json(['message' => "{$asset->symbol} removed from watchlist."]);
    }
}
