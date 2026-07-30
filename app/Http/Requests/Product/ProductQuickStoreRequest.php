<?php

namespace App\Http\Requests\Product;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductQuickStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', Rule::exists((new Category())->getTable(), 'id')],
            'unit_type_id' => ['required', 'integer', Rule::exists((new UnitType())->getTable(), 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists((new Supplier())->getTable(), 'id')],
            'buying_price' => ['required', 'numeric', 'gte:0'],
            'selling_price' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
