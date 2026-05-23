<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'side'     => ['required', Rule::in([Order::SIDE_BUY, Order::SIDE_SELL])],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min' => 'Minimum order size is 0.001 troy oz.',
            'quantity.max' => 'Maximum single order is 10,000 troy oz.',
        ];
    }
}
