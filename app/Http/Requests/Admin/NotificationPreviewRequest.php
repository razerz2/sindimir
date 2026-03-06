<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aluno_id' => ['required', 'integer', 'exists:alunos,id'],
            'curso_id' => ['required', 'integer', 'exists:cursos,id'],
            'notification_type' => ['required', 'string', Rule::in(NotificationType::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'notification_type.in' => 'Tipo de notificação inválido.',
        ];
    }
}
