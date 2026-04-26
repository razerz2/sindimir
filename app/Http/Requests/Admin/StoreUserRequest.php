<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    private const BRAZIL_WHATSAPP_REGEX = '/^(?:1[1-9]|2[12478]|3[1-578]|4[1-69]|5[1345]|6[1-9]|7[134579]|8[1-9]|9[1-9])9\d{8}$/';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nome_exibicao' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:' . self::BRAZIL_WHATSAPP_REGEX],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => [
                'required',
                Rule::in([UserRole::Admin->value, UserRole::Usuario->value]),
            ],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => [Rule::in(array_keys(User::MODULES))],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp.required' => 'Informe o WhatsApp.',
            'whatsapp.string' => 'Informe um WhatsApp válido.',
            'whatsapp.max' => 'O WhatsApp deve ter no máximo :max caracteres.',
            'whatsapp.regex' => 'Informe um WhatsApp válido no formato brasileiro com DDD.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp' => $this->normalizeWhatsapp($this->input('whatsapp')),
        ]);
    }

    private function normalizeWhatsapp(mixed $value): ?string
    {
        $normalized = Phone::normalize(is_scalar($value) ? (string) $value : null);
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '55') && strlen($normalized) === 13) {
            return substr($normalized, 2);
        }

        return $normalized;
    }
}
