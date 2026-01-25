<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MunicipioStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Municipio::class) ?? false;
    }

    public function rules(): array
    {
        $estadoId = $this->input('estado_id');

        return [
            'estado_id' => [
                'required',
                'integer',
                Rule::exists('estados', 'id')->where('ativo', true),
            ],
            'nome' => [
                'required',
                'string',
                'max:120',
                Rule::unique('municipios', 'nome')->where('estado_id', $estadoId),
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
