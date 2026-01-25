<?php

namespace App\Http\Requests\Admin;

use App\Models\Municipio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MunicipioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $municipio = $this->route('municipio');

        return $municipio instanceof Municipio
            ? $this->user()?->can('update', $municipio) ?? false
            : false;
    }

    public function rules(): array
    {
        /** @var Municipio|null $municipio */
        $municipio = $this->route('municipio');
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
                Rule::unique('municipios', 'nome')
                    ->where('estado_id', $estadoId)
                    ->ignore($municipio),
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
