@extends('public.layouts.app')

@section('title', 'Cadastro de aluno')

@section('content')
    <div class="card">
        <form action="{{ route('public.cadastro.store') }}" method="POST">
            @csrf
            <div>
                <label for="cpf">CPF</label>
                <input id="cpf" name="cpf" type="text" value="{{ old('cpf') }}" required>
            </div>

            <div>
                <label for="data_nascimento">Data de nascimento</label>
                <input id="data_nascimento" name="data_nascimento" type="date" value="{{ old('data_nascimento') }}">
            </div>

            <div>
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="">Não informado</option>
                    @foreach ($selects['sexo'] as $sexo)
                        <option value="{{ $sexo->value }}" {{ old('sexo') === $sexo->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $sexo->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="nome_completo">Nome completo</label>
                <input id="nome_completo" name="nome_completo" type="text" value="{{ old('nome_completo') }}" required>
            </div>

            <div>
                <label for="nome_social">Nome social</label>
                <input id="nome_social" name="nome_social" type="text" value="{{ old('nome_social') }}">
            </div>

            <div>
                <label for="naturalidade">Naturalidade</label>
                <input id="naturalidade" name="naturalidade" type="text" value="{{ old('naturalidade') }}">
            </div>

            <div>
                <label for="nacionalidade">Nacionalidade</label>
                <input id="nacionalidade" name="nacionalidade" type="text" value="{{ old('nacionalidade') }}">
            </div>

            <div>
                <label for="uf_naturalidade">UF (naturalidade)</label>
                <input id="uf_naturalidade" name="uf_naturalidade" type="text" maxlength="2" value="{{ old('uf_naturalidade') }}">
            </div>

            <div>
                <label for="nome_pai">Nome do pai</label>
                <input id="nome_pai" name="nome_pai" type="text" value="{{ old('nome_pai') }}">
            </div>

            <div>
                <label for="nome_mae">Nome da mãe</label>
                <input id="nome_mae" name="nome_mae" type="text" value="{{ old('nome_mae') }}">
            </div>

            <div>
                <label for="endereco">Endereço</label>
                <input id="endereco" name="endereco" type="text" value="{{ old('endereco') }}">
            </div>

            <div>
                <label for="bairro">Bairro</label>
                <input id="bairro" name="bairro" type="text" value="{{ old('bairro') }}">
            </div>

            <div>
                <label for="uf_residencia">UF (residência)</label>
                <input id="uf_residencia" name="uf_residencia" type="text" maxlength="2" value="{{ old('uf_residencia') }}">
            </div>

            <div>
                <label for="municipio">Município</label>
                <input id="municipio" name="municipio" type="text" value="{{ old('municipio') }}">
            </div>

            <div>
                <label for="cep">CEP</label>
                <input id="cep" name="cep" type="text" value="{{ old('cep') }}">
            </div>

            <div>
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}">
            </div>

            <div>
                <label for="celular">Celular</label>
                <input id="celular" name="celular" type="text" value="{{ old('celular') }}">
            </div>

            <div>
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" type="text" value="{{ old('telefone') }}">
            </div>

            <div>
                <label for="estado_civil">Estado civil</label>
                <select id="estado_civil" name="estado_civil">
                    <option value="">Não informado</option>
                    @foreach ($selects['estado_civil'] as $estado)
                        <option value="{{ $estado->value }}" {{ old('estado_civil') === $estado->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $estado->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="raca_cor">Raça/Cor</label>
                <select id="raca_cor" name="raca_cor">
                    <option value="">Não informado</option>
                    @foreach ($selects['raca_cor'] as $raca)
                        <option value="{{ $raca->value }}" {{ old('raca_cor') === $raca->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $raca->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="possui_deficiencia">Possui deficiência?</label>
                <select id="possui_deficiencia" name="possui_deficiencia">
                    <option value="">Não informado</option>
                    @foreach ($selects['possui_deficiencia'] as $opcao)
                        <option value="{{ $opcao->value }}" {{ old('possui_deficiencia') === $opcao->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $opcao->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Deficiências</label>
                <div>
                    @foreach ($deficiencias as $deficiencia)
                        <label>
                            <input type="checkbox" name="deficiencias[]" value="{{ $deficiencia->id }}"
                                {{ in_array($deficiencia->id, old('deficiencias', []), true) ? 'checked' : '' }}>
                            {{ $deficiencia->nome }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="deficiencia_descricao">Descrição adicional da deficiência</label>
                <input id="deficiencia_descricao" name="deficiencia_descricao" type="text" value="{{ old('deficiencia_descricao') }}">
            </div>

            <div>
                <label for="escolaridade">Escolaridade</label>
                <select id="escolaridade" name="escolaridade">
                    <option value="">Não informado</option>
                    @foreach ($selects['escolaridade'] as $escolaridade)
                        <option value="{{ $escolaridade->value }}" {{ old('escolaridade') === $escolaridade->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $escolaridade->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="renda_familiar">Renda familiar</label>
                <select id="renda_familiar" name="renda_familiar">
                    <option value="">Não informado</option>
                    @foreach ($selects['renda_familiar'] as $renda)
                        <option value="{{ $renda->value }}" {{ old('renda_familiar') === $renda->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $renda->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estuda">Estuda?</label>
                <select id="estuda" name="estuda">
                    <option value="">Não informado</option>
                    <option value="1" {{ old('estuda') === '1' ? 'selected' : '' }}>Sim</option>
                    <option value="0" {{ old('estuda') === '0' ? 'selected' : '' }}>Não</option>
                </select>
            </div>

            <div>
                <label for="trabalha">Trabalha?</label>
                <select id="trabalha" name="trabalha">
                    <option value="">Não informado</option>
                    <option value="1" {{ old('trabalha') === '1' ? 'selected' : '' }}>Sim</option>
                    <option value="0" {{ old('trabalha') === '0' ? 'selected' : '' }}>Não</option>
                </select>
            </div>

            <div>
                <label for="situacao_participante">Situação do participante</label>
                <select id="situacao_participante" name="situacao_participante">
                    <option value="">Não informado</option>
                    @foreach ($selects['situacao_participante'] as $situacao)
                        <option value="{{ $situacao->value }}" {{ old('situacao_participante') === $situacao->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $situacao->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tipo_entidade_origem">Tipo de entidade de origem</label>
                <select id="tipo_entidade_origem" name="tipo_entidade_origem">
                    <option value="">Não informado</option>
                    @foreach ($selects['tipo_entidade_origem'] as $tipo)
                        <option value="{{ $tipo->value }}" {{ old('tipo_entidade_origem') === $tipo->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $tipo->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="numero_cadastro_unico">Número do Cadastro Único</label>
                <input id="numero_cadastro_unico" name="numero_cadastro_unico" type="text" value="{{ old('numero_cadastro_unico') }}">
            </div>

            <div>
                <label for="recebe_bolsa_familia">Recebe Bolsa Família?</label>
                <select id="recebe_bolsa_familia" name="recebe_bolsa_familia">
                    <option value="">Não informado</option>
                    <option value="1" {{ old('recebe_bolsa_familia') === '1' ? 'selected' : '' }}>Sim</option>
                    <option value="0" {{ old('recebe_bolsa_familia') === '0' ? 'selected' : '' }}>Não</option>
                </select>
            </div>

            <div>
                <label for="responsavel_menor">É responsável por algum menor?</label>
                <select id="responsavel_menor" name="responsavel_menor">
                    <option value="">Não informado</option>
                    <option value="1" {{ old('responsavel_menor') === '1' ? 'selected' : '' }}>Sim</option>
                    <option value="0" {{ old('responsavel_menor') === '0' ? 'selected' : '' }}>Não</option>
                </select>
            </div>

            <div>
                <label for="idade_menor_mais_novo">Idade do menor mais novo</label>
                <input id="idade_menor_mais_novo" name="idade_menor_mais_novo" type="number" min="0" value="{{ old('idade_menor_mais_novo') }}">
            </div>

            <div>
                <label for="tem_com_quem_deixar_menores">Tem com quem deixar os menores?</label>
                <select id="tem_com_quem_deixar_menores" name="tem_com_quem_deixar_menores">
                    <option value="">Não informado</option>
                    <option value="1" {{ old('tem_com_quem_deixar_menores') === '1' ? 'selected' : '' }}>Sim</option>
                    <option value="0" {{ old('tem_com_quem_deixar_menores') === '0' ? 'selected' : '' }}>Não</option>
                </select>
            </div>

            <button type="submit">Finalizar cadastro</button>
        </form>
    </div>
@endsection
