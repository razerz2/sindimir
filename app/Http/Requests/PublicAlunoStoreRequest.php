<?php

namespace App\Http\Requests;

use App\Models\Aluno;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class PublicAlunoStoreRequest extends AlunoBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'evento_curso_id' => ['nullable', 'integer', Rule::exists('evento_cursos', 'id')],
        ]);
    }

    protected function cpfUniqueRule(): Unique
    {
        $alunoId = Aluno::query()
            ->whereCpf($this->input('cpf'))
            ->value('id');

        return Rule::unique('alunos', 'cpf')->ignore($alunoId);
    }
}
