<?php

namespace App\Http\Requests\CashRegister;

use Illuminate\Foundation\Http\FormRequest;

class CashRegisterReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
