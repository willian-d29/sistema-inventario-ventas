<?php

namespace App\Http\Requests\Profile;

use App\Services\UserPreferenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreferenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'required', Rule::in(UserPreferenceService::ALLOWED_THEMES)],
            'high_contrast' => ['sometimes', 'required', 'boolean'],
            'reduced_motion' => ['sometimes', 'required', 'boolean'],
            'font_scale' => ['sometimes', 'required', Rule::in(UserPreferenceService::ALLOWED_FONT_SCALES)],
            'compact_mode' => ['sometimes', 'required', 'boolean'],
            'sidebar_collapsed' => ['sometimes', 'required', 'boolean'],
            'locale' => ['sometimes', 'required', Rule::in(UserPreferenceService::ALLOWED_LOCALES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        app(UserPreferenceService::class)->rejectUnknownKeys($this->all());
    }
}
