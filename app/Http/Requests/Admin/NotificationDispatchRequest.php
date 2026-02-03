<?php

namespace App\Http\Requests\Admin;

use App\Services\ConfiguracaoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NotificationDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'curso_id' => ['nullable', 'required_without:evento_curso_id', 'exists:cursos,id'],
            'evento_curso_id' => ['nullable', 'required_without:curso_id', 'exists:evento_cursos,id'],
            'aluno_ids' => ['array'],
            'aluno_ids.*' => ['integer', 'exists:alunos,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var ConfiguracaoService $configuracaoService */
            $configuracaoService = app(ConfiguracaoService::class);
            $destinatarios = (string) $configuracaoService->get('notificacao.destinatarios', 'alunos');
            $destinatarios = in_array($destinatarios, ['alunos', 'contatos_externos', 'ambos'], true)
                ? $destinatarios
                : 'alunos';

            if (
                $this->filled('curso_id')
                && ! $this->filled('aluno_ids')
                && $destinatarios === 'alunos'
            ) {
                $validator->errors()->add('aluno_ids', 'Informe ao menos um aluno para o curso selecionado.');
            }
        });
    }
}
