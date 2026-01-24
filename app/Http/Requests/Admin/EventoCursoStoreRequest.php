<?php

namespace App\Http\Requests\Admin;

use App\Enums\TurnoEvento;
use App\Models\EventoCurso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoCursoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EventoCurso::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'numero_evento' => ['required', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'carga_horaria' => ['required', 'integer', 'min:0'],
            'municipio' => ['required', 'string', 'max:255'],
            'local_realizacao' => ['required', 'string', 'max:255'],
            'turno' => ['nullable', Rule::in(array_map(fn (TurnoEvento $turno) => $turno->value, TurnoEvento::cases()))],
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
