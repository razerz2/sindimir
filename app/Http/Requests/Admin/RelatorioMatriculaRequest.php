<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusMatricula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioMatriculaRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(array_map(
                fn (StatusMatricula $status) => $status->value,
                StatusMatricula::cases()
            ))],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'canal_origem' => ['nullable', Rule::in(['manual', 'notificacao'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
