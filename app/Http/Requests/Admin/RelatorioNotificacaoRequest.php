<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelatorioNotificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_type' => ['nullable', Rule::in(array_map(
                fn (NotificationType $type) => $type->value,
                NotificationType::cases()
            ))],
            'canal' => ['nullable', Rule::in(['email', 'whatsapp'])],
            'status' => ['nullable', Rule::in(['success', 'failed', 'blocked'])],
            'curso_id' => ['nullable', 'integer', 'exists:cursos,id'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}
