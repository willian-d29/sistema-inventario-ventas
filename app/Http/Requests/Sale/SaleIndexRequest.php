<?php

namespace App\Http\Requests\Sale;

use App\Enums\Core\SortOrderEnum;
use App\Enums\Transaction\PaymentMethodEnum;
use App\Http\Requests\BaseIndexRequest;
use Illuminate\Validation\Rule;

class SaleIndexRequest extends BaseIndexRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cashier_id' => ['nullable', 'integer'],
            'document_type' => ['nullable', 'string', Rule::in(['receipt', 'invoice'])],
            'full_document_number' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', Rule::in(PaymentMethodEnum::values())],
            'status' => ['nullable', 'string', Rule::in(['paid', 'cancelled', 'refunded'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'amount_min' => ['nullable', 'numeric', 'gte:0'],
            'amount_max' => ['nullable', 'numeric', 'gte:0'],
            'sort_order' => ['nullable', Rule::in(SortOrderEnum::values())],
            'inertia' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
