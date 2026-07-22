<?php

namespace App\Http\Requests\Sale;

use App\Enums\Core\AmountTypeEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['receipt', 'invoice'])],
            'payments' => ['required', 'array', 'min:1', 'max:2'],
            'payments.*.method' => ['required', 'string', Rule::in(PaymentMethodEnum::values())],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.received_amount' => ['nullable', 'numeric', 'gte:0'],
            'payments.*.operation_number' => ['nullable', 'string', 'max:255'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:255'],
            'payments.*.notes' => ['nullable', 'string', 'max:1000'],
            'custom_discount' => ['nullable', 'array'],
            'custom_discount.discount' => ['required_with:custom_discount', 'numeric', 'gte:0'],
            'custom_discount.discount_type' => ['required_with:custom_discount', 'string', Rule::in(AmountTypeEnum::values())],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $payments = $this->input('payments', []);
                $methods = collect($payments)->pluck('method')->filter();

                if ($methods->count() !== $methods->unique()->count()) {
                    $validator->errors()->add('payments', 'No repitas el mismo método de pago en una venta mixta.');
                }

                foreach ($payments as $index => $payment) {
                    if (($payment['method'] ?? null) !== PaymentMethodEnum::CASH->value) {
                        continue;
                    }

                    if (! array_key_exists('received_amount', $payment) || $payment['received_amount'] === null || $payment['received_amount'] === '') {
                        $validator->errors()->add("payments.$index.received_amount", 'Ingresa el efectivo recibido.');
                        continue;
                    }

                    if ((float) $payment['received_amount'] < (float) ($payment['amount'] ?? 0)) {
                        $validator->errors()->add("payments.$index.received_amount", 'El efectivo recibido no cubre el monto.');
                    }
                }
            },
        ];
    }
}
