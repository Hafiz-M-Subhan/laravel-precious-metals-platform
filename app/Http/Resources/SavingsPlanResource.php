<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'asset'             => new AssetResource($this->whenLoaded('asset')),
            'amount_per_cycle'  => (float) $this->amount_per_cycle,
            'currency'          => $this->currency,
            'frequency'         => $this->frequency,
            'execution_day'     => $this->execution_day,
            'status'            => $this->status,
            'total_invested'    => (float) $this->total_invested,
            'total_quantity'    => (float) $this->total_quantity,
            'avg_cost_basis'    => (float) $this->total_quantity > 0 ? $this->averageCostBasis() : null,
            'last_executed_at'  => $this->last_executed_at?->toIso8601String(),
            'next_execution_at' => $this->next_execution_at?->toIso8601String(),
            'created_at'        => $this->created_at->toIso8601String(),
        ];
    }
}
