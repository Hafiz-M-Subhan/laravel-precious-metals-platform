<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'asset'        => new AssetResource($this->whenLoaded('asset')),
            'condition'    => $this->condition,
            'target_price' => (float) $this->target_price,
            'note'         => $this->note,
            'is_active'    => $this->is_active,
            'triggered_at' => $this->triggered_at?->toIso8601String(),
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
