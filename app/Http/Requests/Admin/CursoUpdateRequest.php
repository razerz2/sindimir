<?php

namespace App\Http\Requests\Admin;

use App\Models\Curso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where('ativo', true),
            ],
            'validade' => ['nullable', 'date'],
            'limite_vagas' => ['required', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
            'validade' => $this->input('validade') ?: null,
        ]);
    }
}
