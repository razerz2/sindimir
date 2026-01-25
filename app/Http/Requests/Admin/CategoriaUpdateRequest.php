<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $categoria = $this->route('categoria');

        return $categoria instanceof Categoria
            ? $this->user()?->can('update', $categoria) ?? false
            : false;
    }

    public function rules(): array
    {
        /** @var Categoria|null $categoria */
        $categoria = $this->route('categoria');

        return [
            'nome' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categorias', 'nome')->ignore($categoria),
            ],
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
