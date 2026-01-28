<?php

namespace App\Http\Requests;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use App\Http\Requests\Concerns\NormalizesCpf;
use App\Support\Cpf;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AlunoBaseRequest extends FormRequest
{
    use NormalizesCpf;

    protected function baseRules(): array
    {
        return [
            'cpf' => [
                'required',
                'string',
                'digits:11',
                $this->cpfUniqueRule(),
                function (string $attribute, mixed $value, $fail) {
                    if (! Cpf::isValid($value)) {
                        $fail('CPF inválido.');
                    }
                },
            ],
            'nome_completo' => ['required', 'string', 'max:255'],
            'data_nascimento' => ['required', 'date'],
            'sexo' => ['required', Rule::in(array_map(fn (Sexo $sexo) => $sexo->value, Sexo::cases()))],
            'celular' => ['required', 'string', 'digits_between:10,11', $this->celularUniqueRule()],
            'email' => ['required', 'email', 'max:255', $this->emailUniqueRule()],
            'endereco' => ['required', 'string', 'max:255'],
            'bairro' => ['required', 'string', 'max:255'],
            'estado_residencia_id' => [
                'required',
                'integer',
                Rule::exists('estados', 'id')->where('ativo', true),
            ],
            'municipio_id' => [
                'required',
                'integer',
                Rule::exists('municipios', 'id')->where(function ($query) {
                    $query->where('estado_id', $this->input('estado_residencia_id'))
                        ->where('ativo', true);
                }),
            ],
            'cep' => ['required', 'string', 'max:10'],
        ];
    }

    protected function optionalRules(): array
    {
        return [
            'nome_social' => ['nullable', 'string', 'max:255'],
            'naturalidade' => ['nullable', 'string', 'max:255'],
            'nacionalidade' => ['nullable', 'string', 'max:255'],
            'uf_naturalidade' => ['nullable', 'string', 'size:2'],
            'nome_pai' => ['nullable', 'string', 'max:255'],
            'nome_mae' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'digits_between:10,11', $this->telefoneUniqueRule()],
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

    protected function cpfUniqueRule(): Unique
    {
        return Rule::unique('alunos', 'cpf');
    }

    protected function emailUniqueRule(): Unique
    {
        return Rule::unique('alunos', 'email');
    }

    protected function celularUniqueRule(): Unique
    {
        return Rule::unique('alunos', 'celular');
    }

    protected function telefoneUniqueRule(): Unique
    {
        return Rule::unique('alunos', 'telefone');
    }

    public function messages(): array
    {
        return [
            'nome_completo.required' => 'Informe o nome completo.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.unique' => 'CPF já cadastrado',
            'cpf.digits' => 'CPF inválido.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'sexo.required' => 'Selecione o sexo.',
            'celular.required' => 'Informe o celular.',
            'celular.unique' => 'Telefone já cadastrado',
            'celular.digits_between' => 'Celular inválido.',
            'email.required' => 'Informe o e-mail.',
            'email.unique' => 'E-mail já cadastrado',
            'endereco.required' => 'Informe o endereço.',
            'bairro.required' => 'Informe o bairro.',
            'estado_residencia_id.required' => 'Selecione o estado.',
            'municipio_id.required' => 'Selecione o município.',
            'cep.required' => 'Informe o CEP.',
            'telefone.unique' => 'Telefone já cadastrado',
            'telefone.digits_between' => 'Telefone inválido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->normalizeCpf($this->input('cpf')),
            'telefone' => $this->normalizeTelefone($this->input('telefone')),
            'celular' => $this->normalizeTelefone($this->input('celular')),
            'estuda' => $this->boolean('estuda'),
            'trabalha' => $this->boolean('trabalha'),
            'recebe_bolsa_familia' => $this->boolean('recebe_bolsa_familia'),
            'responsavel_menor' => $this->boolean('responsavel_menor'),
            'tem_com_quem_deixar_menores' => $this->boolean('tem_com_quem_deixar_menores'),
        ]);
    }

    private function normalizeTelefone(?string $telefone): ?string
    {
        if ($telefone === null) {
            return null;
        }

        $telefone = Phone::normalize($telefone);

        return $telefone === '' ? null : $telefone;
    }
}
