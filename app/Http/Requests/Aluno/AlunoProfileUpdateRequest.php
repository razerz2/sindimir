<?php

namespace App\Http\Requests\Aluno;

use App\Enums\UserRole;
use App\Http\Requests\AlunoBaseRequest;
use Illuminate\Validation\Rule;

class AlunoProfileUpdateRequest extends AlunoBaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->role === UserRole::Aluno && $user->aluno;
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->optionalRules());
    }

    protected function cpfUniqueRule(): \Illuminate\Validation\Rules\Unique
    {
        $alunoId = $this->user()?->aluno?->id;

        return Rule::unique('alunos', 'cpf')->ignore($alunoId);
    }

    protected function emailUniqueRule(): \Illuminate\Validation\Rules\Unique
    {
        $alunoId = $this->user()?->aluno?->id;

        return Rule::unique('alunos', 'email')->ignore($alunoId);
    }

    protected function celularUniqueRule(): \Illuminate\Validation\Rules\Unique
    {
        $alunoId = $this->user()?->aluno?->id;

        return Rule::unique('alunos', 'celular')->ignore($alunoId);
    }

    protected function telefoneUniqueRule(): \Illuminate\Validation\Rules\Unique
    {
        $alunoId = $this->user()?->aluno?->id;

        return Rule::unique('alunos', 'telefone')->ignore($alunoId);
    }
}
