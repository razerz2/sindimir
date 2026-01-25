<?php

namespace App\Http\Requests\Admin;

use App\Models\Estado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class EstadoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $estado = $this->route('estado');

        return $estado instanceof Estado
            ? $this->user()?->can('update', $estado) ?? false
            : false;
    }

    public function rules(): array
    {
        /** @var Estado|null $estado */
        $estado = $this->route('estado');

        return [
            'nome' => ['required', 'string', 'max:120'],
            'uf' => ['required', 'string', 'size:2', Rule::unique('estados', 'uf')->ignore($estado)],
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
