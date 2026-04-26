<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    private const BRAZIL_WHATSAPP_REGEX = '/^(?:1[1-9]|2[12478]|3[1-578]|4[1-69]|5[1345]|6[1-9]|7[134579]|8[1-9]|9[1-9])9\d{8}$/';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'nome_exibicao' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user),
            ],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:' . self::BRAZIL_WHATSAPP_REGEX],
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
