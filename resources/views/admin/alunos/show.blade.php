@extends('admin.layouts.app')

@section('title', 'Detalhes do aluno')

@section('content')
    <p><strong>Nome:</strong> {{ $aluno->nome_completo }}</p>
    <p><strong>CPF:</strong> {{ $aluno->cpf }}</p>
    <p><strong>Data de nascimento:</strong> {{ $aluno->data_nascimento?->format('d/m/Y') ?? '-' }}</p>
    <p><strong>Sexo:</strong> {{ $aluno->sexo?->value ? ucfirst(str_replace('_', ' ', $aluno->sexo->value)) : '-' }}</p>
    <p><strong>Nome social:</strong> {{ $aluno->nome_social ?? '-' }}</p>
    <p><strong>Naturalidade:</strong> {{ $aluno->naturalidade ?? '-' }}</p>
    <p><strong>Nacionalidade:</strong> {{ $aluno->nacionalidade ?? '-' }}</p>
    <p><strong>UF:</strong> {{ $aluno->uf_naturalidade ?? '-' }}</p>
    <p><strong>Nome do pai:</strong> {{ $aluno->nome_pai ?? '-' }}</p>
    <p><strong>Nome da mãe:</strong> {{ $aluno->nome_mae ?? '-' }}</p>
    <p><strong>Endereço:</strong> {{ $aluno->endereco ?? '-' }}</p>
    <p><strong>Bairro:</strong> {{ $aluno->bairro ?? '-' }}</p>
    <p><strong>UF (residência):</strong> {{ $aluno->uf_residencia ?? '-' }}</p>
    <p><strong>Município:</strong> {{ $aluno->municipio ?? '-' }}</p>
    <p><strong>CEP:</strong> {{ $aluno->cep ?? '-' }}</p>
    <p><strong>E-mail:</strong> {{ $aluno->email ?? '-' }}</p>
    <p><strong>Celular:</strong> {{ $aluno->celular ?? '-' }}</p>
    <p><strong>Telefone:</strong> {{ $aluno->telefone ?? '-' }}</p>
    <p><strong>Estado civil:</strong> {{ $aluno->estado_civil?->value ? ucfirst(str_replace('_', ' ', $aluno->estado_civil->value)) : '-' }}</p>
    <p><strong>Raça/Cor:</strong> {{ $aluno->raca_cor?->value ? ucfirst(str_replace('_', ' ', $aluno->raca_cor->value)) : '-' }}</p>
    <p><strong>Possui deficiência:</strong> {{ $aluno->possui_deficiencia?->value ? ucfirst(str_replace('_', ' ', $aluno->possui_deficiencia->value)) : '-' }}</p>
    <p><strong>Escolaridade:</strong> {{ $aluno->escolaridade?->value ? ucfirst(str_replace('_', ' ', $aluno->escolaridade->value)) : '-' }}</p>
    <p><strong>Renda familiar:</strong> {{ $aluno->renda_familiar?->value ? ucfirst(str_replace('_', ' ', $aluno->renda_familiar->value)) : '-' }}</p>
    <p><strong>Estuda:</strong> {{ $aluno->estuda === null ? '-' : ($aluno->estuda ? 'Sim' : 'Não') }}</p>
    <p><strong>Trabalha:</strong> {{ $aluno->trabalha === null ? '-' : ($aluno->trabalha ? 'Sim' : 'Não') }}</p>
    <p><strong>Situação:</strong> {{ $aluno->situacao_participante?->value ? ucfirst(str_replace('_', ' ', $aluno->situacao_participante->value)) : '-' }}</p>
    <p><strong>Entidade de origem:</strong> {{ $aluno->tipo_entidade_origem?->value ? ucfirst(str_replace('_', ' ', $aluno->tipo_entidade_origem->value)) : '-' }}</p>
    <p><strong>Cadastro Único:</strong> {{ $aluno->numero_cadastro_unico ?? '-' }}</p>
    <p><strong>Recebe Bolsa Família:</strong> {{ $aluno->recebe_bolsa_familia === null ? '-' : ($aluno->recebe_bolsa_familia ? 'Sim' : 'Não') }}</p>
    <p><strong>Responsável por menor:</strong> {{ $aluno->responsavel_menor === null ? '-' : ($aluno->responsavel_menor ? 'Sim' : 'Não') }}</p>
    <p><strong>Idade do menor mais novo:</strong> {{ $aluno->idade_menor_mais_novo ?? '-' }}</p>
    <p><strong>Tem com quem deixar menores:</strong> {{ $aluno->tem_com_quem_deixar_menores === null ? '-' : ($aluno->tem_com_quem_deixar_menores ? 'Sim' : 'Não') }}</p>

    <p><strong>Deficiências:</strong>
        {{ $aluno->deficiencias->isEmpty() ? '-' : $aluno->deficiencias->pluck('nome')->implode(', ') }}
    </p>

    <div>
        <a href="{{ route('admin.alunos.edit', $aluno) }}">Editar</a>
        <a href="{{ route('admin.alunos.index') }}">Voltar</a>
    </div>
@endsection
