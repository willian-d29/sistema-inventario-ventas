<?php

namespace App\Http\Requests\CashRegister;

use App\Enums\CashRegister\CashMovementDirectionEnum;
use App\Enums\CashRegister\CashMovementTypeEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashRegisterMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([
                CashMovementTypeEnum::MANUAL_INCOME->value,
                CashMovementTypeEnum::WITHDRAWAL->value,
                CashMovementTypeEnum::ADJUSTMENT->value,
            ])],
            'direction' => ['nullable', 'required_if:type,'.CashMovementTypeEnum::ADJUSTMENT->value, Rule::in(CashMovementDirectionEnum::values())],
            'payment_method' => ['nullable', 'string', Rule::in(PaymentMethodEnum::values())],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'reversed_movement_id' => ['nullable', 'integer', Rule::exists('cash_movements', 'id')],
            'confirmed' => ['nullable', 'boolean'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
