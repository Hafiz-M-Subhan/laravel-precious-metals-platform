<?php

namespace App\Http\Requests;

use App\Models\PriceAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePriceAlertRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'asset_id'     => ['required', 'integer', 'exists:assets,id'],
            'condition'    => ['required', Rule::in([PriceAlert::CONDITION_ABOVE, PriceAlert::CONDITION_BELOW])],
            'target_price' => ['required', 'numeric', 'min:0.01'],
            'note'         => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
