<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'status_evento' => ['nullable', Rule::in(['futuro', 'em_andamento', 'encerrado'])],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'possui_vagas' => ['nullable', Rule::in(['sim', 'nao'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
