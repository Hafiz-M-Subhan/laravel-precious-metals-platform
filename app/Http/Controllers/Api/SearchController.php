<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssetSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly AssetSearchService $search) {}

    public function assets(Request $request): JsonResponse
    {
        $request->validate([
            'q'        => ['required', 'string', 'min:1', 'max:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $results = $this->search->search(
            $request->input('q'),
            $request->only('currency')
        );

        return response()->json([
            'data'  => $results,
            'count' => count($results),
        ]);
    }
}
