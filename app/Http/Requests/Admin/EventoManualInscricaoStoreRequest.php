<?php

namespace App\Http\Requests\Admin;

use App\Models\EventoCurso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoManualInscricaoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evento = $this->route('evento');

        return $evento instanceof EventoCurso
            ? $this->user()?->can('update', $evento) ?? false
            : false;
    }

    public function rules(): array
    {
        return [
            'aluno_id' => [
                'required',
                'integer',
                Rule::exists('alunos', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
