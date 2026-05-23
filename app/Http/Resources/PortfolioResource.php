<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_value'    => (float) $this->resource['total_value'],
            'total_cost'     => (float) $this->resource['total_cost'],
            'unrealized_pnl' => (float) $this->resource['unrealized_pnl'],
            'realized_pnl'   => (float) $this->resource['realized_pnl'],
            'pnl_percentage' => round((float) $this->resource['pnl_percentage'], 2),
            'currency'       => $this->resource['currency'],
            'holdings'       => $this->resource['holdings'],
        ];
    }
}
