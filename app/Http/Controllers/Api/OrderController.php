<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * GET /api/v1/orders
     *
     * Returns the authenticated user's orders, newest first.
     * Uses cursor pagination for constant-time performance across large tables.
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('asset:id,symbol,name,unit')
            ->orderByDesc('created_at')
            ->cursorPaginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/v1/orders
     *
     * Places a market order and immediately queues it for async processing.
     * Returns 202 Accepted — the order is pending, not yet filled.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeMarket(
            userId:  auth()->id(),
            assetId: $request->asset_id,
            side:    $request->side,
            quantity: $request->quantity,
        );

        return response()->json([
            'message' => 'Order queued for processing.',
            'order'   => new OrderResource($order),
        ], 202);
    }

    /**
     * DELETE /api/v1/orders/{order}
     *
     * Cancel a pending order. Policy enforces ownership.
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $this->orderService->cancel($order);

        return response()->json(['message' => 'Order cancelled.']);
    }
}
