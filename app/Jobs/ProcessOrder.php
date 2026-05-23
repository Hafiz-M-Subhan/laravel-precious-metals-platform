<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\OrderFulfilled;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes a market or limit order asynchronously.
 *
 * Queue: orders  (dedicated high-priority queue)
 * Unique key: order ID — prevents double-fill if the job re-queues after failure.
 * Retry strategy: 3 attempts, backoff 5s / 30s / 120s.
 */
class ProcessOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 30, 120];

    public function __construct(public readonly Order $order) {}

    public function uniqueId(): string
    {
        return (string) $this->order->id;
    }

    public function handle(OrderService $service): void
    {
        if (! $this->order->isPending()) {
            return; // Already processed (idempotency guard)
        }

        $this->order->update(['status' => Order::STATUS_PROCESSING]);

        $filledOrder = $service->fill($this->order);

        broadcast(new OrderFulfilled($filledOrder))->toOthers();

        Log::info('Order filled', [
            'order_id' => $filledOrder->id,
            'user_id'  => $filledOrder->user_id,
            'side'     => $filledOrder->side,
            'total'    => $filledOrder->total_amount,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->order->update([
            'status'          => Order::STATUS_FAILED,
            'rejected_reason' => $e->getMessage(),
        ]);

        Log::error('Order processing failed', [
            'order_id' => $this->order->id,
            'error'    => $e->getMessage(),
        ]);
    }
}
