<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'evento_curso_id' => ['nullable', 'integer', 'exists:evento_cursos,id'],
            'status_inscricao' => ['nullable', Rule::in(['ativa', 'cancelada', 'convertida'])],
            'origem' => ['nullable', Rule::in(['manual', 'publica', 'notificacao'])],
            'possui_matricula' => ['nullable', Rule::in(['sim', 'nao'])],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
