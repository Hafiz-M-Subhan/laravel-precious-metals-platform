<?php

namespace App\Http\Requests;

use App\Models\SavingsPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSavingsPlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'asset_id'      => ['required', 'integer', 'exists:assets,id'],
            'amount'        => ['required', 'numeric', 'min:1', 'max:100000'],
            'frequency'     => ['required', Rule::in([
                SavingsPlan::FREQ_MONTHLY,
                SavingsPlan::FREQ_BIWEEKLY,
                SavingsPlan::FREQ_WEEKLY,
            ])],
            'execution_day' => ['sometimes', 'integer', 'min:1', 'max:28'],
            'currency'      => ['sometimes', 'string', 'size:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'        => 'The minimum investment per cycle is $1.00.',
            'amount.max'        => 'The maximum investment per cycle is $100,000.',
            'execution_day.max' => 'Use day 28 or lower to ensure the plan runs in every month.',
        ];
    }
}
