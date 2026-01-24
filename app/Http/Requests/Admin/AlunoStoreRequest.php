<?php

namespace App\Http\Requests\Admin;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use App\Models\Aluno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlunoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Aluno::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'cpf' => ['required', 'string', 'max:14', 'unique:alunos,cpf'],
            'data_nascimento' => ['nullable', 'date'],
            'sexo' => ['nullable', Rule::in(array_map(fn (Sexo $sexo) => $sexo->value, Sexo::cases()))],
            'nome_completo' => ['required', 'string', 'max:255'],
            'nome_social' => ['nullable', 'string', 'max:255'],
            'naturalidade' => ['nullable', 'string', 'max:255'],
            'nacionalidade' => ['nullable', 'string', 'max:255'],
            'uf_naturalidade' => ['nullable', 'string', 'size:2'],
            'nome_pai' => ['nullable', 'string', 'max:255'],
            'nome_mae' => ['nullable', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'uf_residencia' => ['nullable', 'string', 'size:2'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'celular' => ['nullable', 'string', 'max:20'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'estado_civil' => ['nullable', Rule::in(array_map(fn (EstadoCivil $estado) => $estado->value, EstadoCivil::cases()))],
            'raca_cor' => ['nullable', Rule::in(array_map(fn (RacaCor $raca) => $raca->value, RacaCor::cases()))],
            'possui_deficiencia' => ['nullable', Rule::in(array_map(fn (SimNaoNaoDeclarada $item) => $item->value, SimNaoNaoDeclarada::cases()))],
            'escolaridade' => ['nullable', Rule::in(array_map(fn (Escolaridade $item) => $item->value, Escolaridade::cases()))],
            'renda_familiar' => ['nullable', Rule::in(array_map(fn (RendaFamiliar $item) => $item->value, RendaFamiliar::cases()))],
            'estuda' => ['nullable', 'boolean'],
            'trabalha' => ['nullable', 'boolean'],
            'situacao_participante' => ['nullable', Rule::in(array_map(fn (SituacaoParticipante $item) => $item->value, SituacaoParticipante::cases()))],
            'tipo_entidade_origem' => ['nullable', Rule::in(array_map(fn (TipoEntidadeOrigem $item) => $item->value, TipoEntidadeOrigem::cases()))],
            'numero_cadastro_unico' => ['nullable', 'string', 'max:255'],
            'recebe_bolsa_familia' => ['nullable', 'boolean'],
            'responsavel_menor' => ['nullable', 'boolean'],
            'idade_menor_mais_novo' => ['nullable', 'integer', 'min:0'],
            'tem_com_quem_deixar_menores' => ['nullable', 'boolean'],
            'deficiencias' => ['nullable', 'array'],
            'deficiencias.*' => ['integer', 'exists:deficiencias,id'],
            'deficiencia_descricao' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'estuda' => $this->boolean('estuda'),
            'trabalha' => $this->boolean('trabalha'),
            'recebe_bolsa_familia' => $this->boolean('recebe_bolsa_familia'),
            'responsavel_menor' => $this->boolean('responsavel_menor'),
            'tem_com_quem_deixar_menores' => $this->boolean('tem_com_quem_deixar_menores'),
        ]);
    }
}
