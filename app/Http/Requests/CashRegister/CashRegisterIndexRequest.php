<?php

namespace App\Http\Requests\CashRegister;

use App\Enums\CashRegister\CashRegisterStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashRegisterIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'status' => ['nullable', 'string', Rule::in(CashRegisterStatusEnum::values())],
            'difference' => ['nullable', 'string', Rule::in(['with', 'without'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'movement_page' => ['nullable', 'integer', 'min:1'],
            'inertia' => ['nullable', 'string'],
        ];
    }
}
