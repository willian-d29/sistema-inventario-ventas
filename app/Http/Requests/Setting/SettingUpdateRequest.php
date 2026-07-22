<?php

namespace App\Http\Requests\Setting;

use App\Enums\Setting\SettingFieldsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingUpdateRequest extends FormRequest
{
    /**
     * Prepare values serialized by Inertia/FormData before validation.
     */
    protected function prepareForValidation(): void
    {
        $booleanFields = [
            SettingFieldsEnum::THERMAL_SHOW_LOGO->value,
            SettingFieldsEnum::THERMAL_SHOW_CUSTOMER->value,
            SettingFieldsEnum::THERMAL_SHOW_PAYMENT_REFS->value,
            SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value,
            SettingFieldsEnum::RETURN_TO_POS_AFTER_PRINT->value,
            SettingFieldsEnum::KEEP_SALE_CONFIRMATION->value,
        ];

        $integerFields = [
            SettingFieldsEnum::THERMAL_PAPER_WIDTH->value,
            SettingFieldsEnum::PRINT_COPIES->value,
            SettingFieldsEnum::DECIMAL_POINT->value,
        ];

        $data = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }

        foreach ($integerFields as $field) {
            if ($this->has($field) && $this->input($field) !== '') {
                $data[$field] = (int) $this->input($field);
            }
        }

        if ($this->has(SettingFieldsEnum::CURRENCY_CODE->value)) {
            $data[SettingFieldsEnum::CURRENCY_CODE->value] = strtoupper((string) $this->input(SettingFieldsEnum::CURRENCY_CODE->value));
        }

        $this->merge($data);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            SettingFieldsEnum::BUSINESS_NAME->value => ['required', 'string', 'max:120'],
            SettingFieldsEnum::LEGAL_NAME->value => ['nullable', 'string', 'max:160'],
            SettingFieldsEnum::TAX_ID->value => ['nullable', 'string', 'regex:/^\d{11}$/'],
            SettingFieldsEnum::ADDRESS->value => ['nullable', 'string', 'max:255'],
            SettingFieldsEnum::PHONE->value => ['nullable', 'string', 'max:30'],
            SettingFieldsEnum::EMAIL->value => ['nullable', 'email', 'max:120'],
            SettingFieldsEnum::LOGO_PATH->value => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=1600,max_height=1600'],
            SettingFieldsEnum::CURRENCY_CODE->value => ['required', 'string', 'size:3'],
            SettingFieldsEnum::CURRENCY_SYMBOL->value => ['required', 'string', 'max:8'],
            SettingFieldsEnum::TIMEZONE->value => ['required', 'timezone'],
            SettingFieldsEnum::DATE_FORMAT->value => ['required', 'string', Rule::in(['d/m/Y', 'Y-m-d', 'd-m-Y'])],
            SettingFieldsEnum::TIME_FORMAT->value => ['required', 'string', Rule::in(['H:i', 'h:i A'])],
            SettingFieldsEnum::RECEIPT_FOOTER->value => ['nullable', 'string', 'max:255'],
            SettingFieldsEnum::THERMAL_PAPER_WIDTH->value => ['required', 'integer', Rule::in([80])],
            SettingFieldsEnum::THERMAL_SHOW_LOGO->value => ['required', 'boolean'],
            SettingFieldsEnum::THERMAL_SHOW_CUSTOMER->value => ['required', 'boolean'],
            SettingFieldsEnum::THERMAL_SHOW_PAYMENT_REFS->value => ['required', 'boolean'],
            SettingFieldsEnum::PRINT_COPIES->value => ['required', 'integer', 'min:1', 'max:5'],
            SettingFieldsEnum::AUTO_OPEN_PRINT_DIALOG->value => ['required', 'boolean'],
            SettingFieldsEnum::RETURN_TO_POS_AFTER_PRINT->value => ['required', 'boolean'],
            SettingFieldsEnum::KEEP_SALE_CONFIRMATION->value => ['required', 'boolean'],
            SettingFieldsEnum::DEFAULT_SALE_DOCUMENT->value => ['required', 'string', Rule::in(['receipt', 'invoice'])],
            SettingFieldsEnum::CASH_REGISTER_NAME->value => ['required', 'string', 'max:80'],
            SettingFieldsEnum::DECIMAL_POINT->value => ['required', 'integer', 'min:0', 'max:8'],
            SettingFieldsEnum::DISCOUNT->value => ['required', 'numeric', 'gte:0'],
            SettingFieldsEnum::TAX->value => ['required', 'numeric', 'gte:0', 'lte:100'],
        ];
    }
}
