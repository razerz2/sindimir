<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AlunoBaseRequest;
use App\Models\Aluno;

class AlunoStoreRequest extends AlunoBaseRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Aluno::class) ?? false;
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), $this->optionalRules(), [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
    }
}
