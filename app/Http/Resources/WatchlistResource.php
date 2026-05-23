<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'asset' => new AssetResource($this->whenLoaded('asset')),
            'added_at' => $this->created_at->toIso8601String(),
        ];
    }
}
