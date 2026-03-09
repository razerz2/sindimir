<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MatriculaRemocaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acao' => ['nullable', 'string', Rule::in(['mover_espera', 'confirmar'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $acao = strtolower(trim((string) $this->input('acao', 'mover_espera')));
        if ($acao === '') {
            $acao = 'mover_espera';
        }

        $this->merge([
            'acao' => $acao,
        ]);
    }
}
