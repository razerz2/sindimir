<?php

namespace App\Http\Requests\Admin;

use App\Enums\TurnoEvento;
use App\Models\EventoCurso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoCursoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $eventoCurso = $this->route('evento');

        return $eventoCurso instanceof EventoCurso
            ? $this->user()?->can('update', $eventoCurso) ?? false
            : false;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'numero_evento' => ['required', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'horario_inicio' => ['nullable', 'date_format:H:i'],
            'horario_fim' => ['nullable', 'date_format:H:i'],
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
