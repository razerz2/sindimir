<?php

namespace App\Http\Requests\Admin;

use App\Models\EventoCurso;
use Illuminate\Foundation\Http\FormRequest;

class EventoManualInscricaoSearchRequest extends FormRequest
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
            'termo' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'termo' => trim((string) $this->input('termo')),
        ]);
    }
}
