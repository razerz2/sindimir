<?php

namespace App\Http\Requests\Admin;

use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;

class CursoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $curso = $this->route('curso');

        return $curso instanceof Curso
            ? $this->user()?->can('update', $curso) ?? false
            : false;
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
