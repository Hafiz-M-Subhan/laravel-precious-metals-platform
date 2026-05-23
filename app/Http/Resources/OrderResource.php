<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'asset'           => new AssetResource($this->whenLoaded('asset')),
            'side'            => $this->side,
            'type'            => $this->type,
            'quantity'        => (float) $this->quantity,
            'price_per_unit'  => (float) $this->price_per_unit,
            'total_amount'    => (float) $this->total_amount,
            'currency'        => $this->currency,
            'status'          => $this->status,
            'rejected_reason' => $this->when($this->rejected_reason, $this->rejected_reason),
            'filled_at'       => $this->filled_at?->toIso8601String(),
            'created_at'      => $this->created_at->toIso8601String(),
        ];
    }
}
