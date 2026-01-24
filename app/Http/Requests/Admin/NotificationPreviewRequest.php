<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'notification_type' => ['required', 'string', new Enum(NotificationType::class)],
        ];
    }
}
