<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioResource;
use App\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(private readonly PortfolioService $portfolioService) {}

    public function show(Request $request): JsonResponse
    {
        $summary = $this->portfolioService->getSummary($request->user()->id);

        return response()->json(['data' => new PortfolioResource($summary)]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $portfolio = $this->portfolioService->refreshPortfolio($request->user()->id);

        return response()->json([
            'data'    => $portfolio,
            'message' => 'Portfolio values refreshed against live prices.',
        ]);
    }
}
