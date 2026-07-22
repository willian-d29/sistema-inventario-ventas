<?php

namespace App\Http\Requests\CashRegister;

use Illuminate\Foundation\Http\FormRequest;

class CashRegisterOpenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opening_amount' => ['required', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
