<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusListaEspera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioListaEsperaRequest extends FormRequest
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
                fn (StatusListaEspera $status) => $status->value,
                StatusListaEspera::cases()
            ))],
            'posicao_max' => ['nullable', 'integer', 'min:1'],
            'possui_matricula' => ['nullable', Rule::in(['sim', 'nao'])],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
