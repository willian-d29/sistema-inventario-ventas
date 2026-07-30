<?php

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public const ACCOUNT_DOMAIN = 'laratory.pe';

    private function roleSuffix(): string
    {
        return $this->user()?->role === 'admin' ? 'a' : 'c';
    }

    protected function prepareForValidation(): void
    {
        $localPart = str($this->input('email_local', ''))
            ->lower()
            ->replace('@'.self::ACCOUNT_DOMAIN, '')
            ->trim()
            ->toString();

        if ($localPart !== '') {
            $this->merge([
                'email_local' => $localPart,
                'email' => $localPart.'@'.self::ACCOUNT_DOMAIN,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $emailLocalRules = ['required', 'string', 'lowercase', 'max:64', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'];
        $emailLocalRules[] = 'ends_with:.'.$this->roleSuffix();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email_local' => $emailLocalRules,
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'ends_with:@'.self::ACCOUNT_DOMAIN, Rule::unique(User::class)->ignore($this->user()->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'email_local.regex' => 'Usa solo letras, números, puntos, guiones o guion bajo. No incluyas el dominio.',
            'email_local.ends_with' => 'El usuario del correo debe terminar en .'.$this->roleSuffix().' según el rol de la cuenta.',
            'email.ends_with' => 'El correo debe mantenerse dentro del dominio @'.self::ACCOUNT_DOMAIN.'.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email_local' => 'usuario del correo',
        ];
    }
}
