<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AlunoBaseRequest;
use App\Models\Aluno;
use Illuminate\Validation\Rule;

class AlunoUpdateRequest extends AlunoBaseRequest
{
    public function authorize(): bool
    {
        $aluno = $this->route('aluno');

        return $aluno instanceof Aluno
            ? $this->user()?->can('update', $aluno) ?? false
            : false;
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->optionalRules(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }

    protected function cpfUniqueRule(): Rule
    {
        $aluno = $this->route('aluno');

        return Rule::unique('alunos', 'cpf')->ignore($aluno?->id);
    }

    protected function emailUniqueRule(): Rule
    {
        $aluno = $this->route('aluno');

        return Rule::unique('alunos', 'email')->ignore($aluno?->id);
    }

    protected function celularUniqueRule(): Rule
    {
        $aluno = $this->route('aluno');

        return Rule::unique('alunos', 'celular')->ignore($aluno?->id);
    }

    protected function telefoneUniqueRule(): Rule
    {
        $aluno = $this->route('aluno');

        return Rule::unique('alunos', 'telefone')->ignore($aluno?->id);
    }
}
