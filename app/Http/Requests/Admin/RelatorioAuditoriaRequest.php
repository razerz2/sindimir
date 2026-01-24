<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioAuditoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'status_changed'])],
            'entity' => ['nullable', Rule::in(['curso', 'evento', 'aluno', 'inscricao', 'matricula', 'notificacao'])],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
