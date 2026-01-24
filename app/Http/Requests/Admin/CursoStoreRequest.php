<?php

namespace App\Http\Requests\Admin;

use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;

class CursoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Curso::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'validade' => ['nullable', 'date'],
            'limite_vagas' => ['required', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
        ]);
    }
}
