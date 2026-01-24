<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'status' => ['nullable', Rule::in(['ativo', 'inativo'])],
            'possui_eventos_ativos' => ['nullable', Rule::in(['sim', 'nao'])],
            'possui_vagas' => ['nullable', Rule::in(['sim', 'nao'])],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
