<?php

namespace App\Http\Requests\Admin;

use App\Models\Estado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class EstadoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Estado::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'uf' => ['required', 'string', 'size:2', Rule::unique('estados', 'uf')],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'uf' => Str::upper((string) $this->input('uf')),
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
