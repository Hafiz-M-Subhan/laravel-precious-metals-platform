<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'symbol'           => $this->symbol,
            'name'             => $this->name,
            'unit'             => $this->unit,
            'currency'         => $this->currency,
            'spot_price'       => (float) $this->spot_price,
            'bid_price'        => (float) $this->bid_price,
            'ask_price'        => (float) $this->ask_price,
            'spread'           => (float) $this->spread,
            'daily_change'     => (float) $this->daily_change,
            'daily_change_pct' => (float) $this->daily_change_pct,
            'is_active'        => $this->is_active,
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
