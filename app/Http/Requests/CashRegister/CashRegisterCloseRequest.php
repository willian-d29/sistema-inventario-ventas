<?php

namespace App\Http\Requests\CashRegister;

use Illuminate\Foundation\Http\FormRequest;

class CashRegisterCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'declared_amounts' => ['required', 'array'],
            'declared_amounts.cash' => ['required', 'numeric', 'gte:0'],
            'declared_amounts.yape' => ['nullable', 'numeric', 'gte:0'],
            'declared_amounts.plin' => ['nullable', 'numeric', 'gte:0'],
            'declared_amounts.card' => ['nullable', 'numeric', 'gte:0'],
            'declared_amounts.transfer' => ['nullable', 'numeric', 'gte:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'gte:0'],
            'confirmed' => ['nullable', 'boolean'],
        ];
    }
}
