@extends('layouts.public')

@section('title', 'Cadastro de aluno')

@section('content')
    <section class="section">
        <h1 class="section-title">Cadastro de aluno</h1>
        <p class="section-subtitle">
            Preencha os dados abaixo para concluir seu cadastro e seguir com a inscrição.
        </p>
        <div class="content-card card">
            <form action="{{ route('public.cadastro.store') }}" method="POST" class="space-y-8 form">
                @csrf
                <div>
                    <h3 class="text-base font-semibold">Identificacao e vinculo</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="cpf" class="text-sm font-semibold text-[var(--content-text)]">CPF</label>
                            <input id="cpf" name="cpf" type="text" value="{{ old('cpf') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="nome_completo" class="text-sm font-semibold text-[var(--content-text)]">Nome completo</label>
                            <input id="nome_completo" name="nome_completo" type="text" value="{{ old('nome_completo') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="nome_social" class="text-sm font-semibold text-[var(--content-text)]">Nome social</label>
                            <input id="nome_social" name="nome_social" type="text" value="{{ old('nome_social') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="data_nascimento" class="text-sm font-semibold text-[var(--content-text)]">Data de nascimento</label>
                            <input id="data_nascimento" name="data_nascimento" type="date" value="{{ old('data_nascimento') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="sexo" class="text-sm font-semibold text-[var(--content-text)]">Sexo</label>
                            <select id="sexo" name="sexo" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['sexo'] as $sexo)
                                    <option value="{{ $sexo->value }}" {{ old('sexo') === $sexo->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $sexo->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Naturalidade e familia</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="naturalidade" class="text-sm font-semibold text-[var(--content-text)]">Naturalidade</label>
                            <input id="naturalidade" name="naturalidade" type="text" value="{{ old('naturalidade') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="nacionalidade" class="text-sm font-semibold text-[var(--content-text)]">Nacionalidade</label>
                            <input id="nacionalidade" name="nacionalidade" type="text" value="{{ old('nacionalidade') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="uf_naturalidade" class="text-sm font-semibold text-[var(--content-text)]">UF (naturalidade)</label>
                            <input id="uf_naturalidade" name="uf_naturalidade" type="text" maxlength="2" value="{{ old('uf_naturalidade') }}" placeholder="UF" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="nome_pai" class="text-sm font-semibold text-[var(--content-text)]">Nome do pai</label>
                            <input id="nome_pai" name="nome_pai" type="text" value="{{ old('nome_pai') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="nome_mae" class="text-sm font-semibold text-[var(--content-text)]">Nome da mae</label>
                            <input id="nome_mae" name="nome_mae" type="text" value="{{ old('nome_mae') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Endereco e contato</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="endereco" class="text-sm font-semibold text-[var(--content-text)]">Endereco</label>
                            <input id="endereco" name="endereco" type="text" value="{{ old('endereco') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="bairro" class="text-sm font-semibold text-[var(--content-text)]">Bairro</label>
                            <input id="bairro" name="bairro" type="text" value="{{ old('bairro') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="estado_residencia_id" class="text-sm font-semibold text-[var(--content-text)]">UF (residencia)</label>
                            <select id="estado_residencia_id" name="estado_residencia_id" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Selecione</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}" {{ old('estado_residencia_id') == $estado->id ? 'selected' : '' }}>
                                        {{ $estado->nome }} ({{ $estado->uf }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="municipio_id" class="text-sm font-semibold text-[var(--content-text)]">Municipio</label>
                            <select id="municipio_id" name="municipio_id" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Selecione o estado</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="cep" class="text-sm font-semibold text-[var(--content-text)]">CEP</label>
                            <input id="cep" name="cep" type="text" value="{{ old('cep') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-sm font-semibold text-[var(--content-text)]">E-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="celular" class="text-sm font-semibold text-[var(--content-text)]">Celular</label>
                            <input id="celular" name="celular" type="text" value="{{ old('celular') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="telefone" class="text-sm font-semibold text-[var(--content-text)]">Telefone</label>
                            <input id="telefone" name="telefone" type="text" value="{{ old('telefone') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Informacoes socioeconomicas</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="estado_civil" class="text-sm font-semibold text-[var(--content-text)]">Estado civil</label>
                            <select id="estado_civil" name="estado_civil" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['estado_civil'] as $estado)
                                    <option value="{{ $estado->value }}" {{ old('estado_civil') === $estado->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $estado->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="raca_cor" class="text-sm font-semibold text-[var(--content-text)]">Raca/Cor</label>
                            <select id="raca_cor" name="raca_cor" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['raca_cor'] as $raca)
                                    <option value="{{ $raca->value }}" {{ old('raca_cor') === $raca->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $raca->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="escolaridade" class="text-sm font-semibold text-[var(--content-text)]">Escolaridade</label>
                            <select id="escolaridade" name="escolaridade" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['escolaridade'] as $escolaridade)
                                    <option value="{{ $escolaridade->value }}" {{ old('escolaridade') === $escolaridade->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $escolaridade->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="renda_familiar" class="text-sm font-semibold text-[var(--content-text)]">Renda familiar</label>
                            <select id="renda_familiar" name="renda_familiar" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['renda_familiar'] as $renda)
                                    <option value="{{ $renda->value }}" {{ old('renda_familiar') === $renda->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $renda->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="estuda" class="text-sm font-semibold text-[var(--content-text)]">Estuda?</label>
                            <select id="estuda" name="estuda" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                <option value="1" {{ old('estuda') === '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ old('estuda') === '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="trabalha" class="text-sm font-semibold text-[var(--content-text)]">Trabalha?</label>
                            <select id="trabalha" name="trabalha" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                <option value="1" {{ old('trabalha') === '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ old('trabalha') === '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="situacao_participante" class="text-sm font-semibold text-[var(--content-text)]">Situacao do participante</label>
                            <select id="situacao_participante" name="situacao_participante" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['situacao_participante'] as $situacao)
                                    <option value="{{ $situacao->value }}" {{ old('situacao_participante') === $situacao->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $situacao->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="tipo_entidade_origem" class="text-sm font-semibold text-[var(--content-text)]">Tipo de entidade de origem</label>
                            <select id="tipo_entidade_origem" name="tipo_entidade_origem" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['tipo_entidade_origem'] as $tipo)
                                    <option value="{{ $tipo->value }}" {{ old('tipo_entidade_origem') === $tipo->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $tipo->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Programas e beneficios</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="numero_cadastro_unico" class="text-sm font-semibold text-[var(--content-text)]">Numero do Cadastro Unico</label>
                            <input id="numero_cadastro_unico" name="numero_cadastro_unico" type="text" value="{{ old('numero_cadastro_unico') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="recebe_bolsa_familia" class="text-sm font-semibold text-[var(--content-text)]">Recebe Bolsa Familia?</label>
                            <select id="recebe_bolsa_familia" name="recebe_bolsa_familia" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                <option value="1" {{ old('recebe_bolsa_familia') === '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ old('recebe_bolsa_familia') === '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="responsavel_menor" class="text-sm font-semibold text-[var(--content-text)]">Responsavel por menor?</label>
                            <select id="responsavel_menor" name="responsavel_menor" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                <option value="1" {{ old('responsavel_menor') === '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ old('responsavel_menor') === '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="idade_menor_mais_novo" class="text-sm font-semibold text-[var(--content-text)]">Idade do menor mais novo</label>
                            <input id="idade_menor_mais_novo" name="idade_menor_mais_novo" type="number" min="0" value="{{ old('idade_menor_mais_novo') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="tem_com_quem_deixar_menores" class="text-sm font-semibold text-[var(--content-text)]">Tem com quem deixar os menores?</label>
                            <select id="tem_com_quem_deixar_menores" name="tem_com_quem_deixar_menores" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                <option value="1" {{ old('tem_com_quem_deixar_menores') === '1' ? 'selected' : '' }}>Sim</option>
                                <option value="0" {{ old('tem_com_quem_deixar_menores') === '0' ? 'selected' : '' }}>Não</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Deficiencias</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <label for="possui_deficiencia" class="text-sm font-semibold text-[var(--content-text)]">Possui deficiencia?</label>
                            <select id="possui_deficiencia" name="possui_deficiencia" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Não informado</option>
                                @foreach ($selects['possui_deficiencia'] as $opcao)
                                    <option value="{{ $opcao->value }}" {{ old('possui_deficiencia') === $opcao->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $opcao->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label for="deficiencia_descricao" class="text-sm font-semibold text-[var(--content-text)]">Descricao adicional</label>
                            <input id="deficiencia_descricao" name="deficiencia_descricao" type="text" value="{{ old('deficiencia_descricao') }}" class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-semibold">Deficiencias</p>
                        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($deficiencias as $deficiencia)
                                <label class="flex items-center gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="deficiencias[]"
                                        value="{{ $deficiencia->id }}"
                                        class="h-4 w-4 rounded border-[var(--border-color)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]/40"
                                        {{ in_array($deficiencia->id, old('deficiencias', []), true) ? 'checked' : '' }}
                                    >
                                    <span>{{ $deficiencia->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <input type="hidden" id="municipios_fetch_url" value="{{ route('public.catalogo.estados.municipios', ['estado' => 'STATE_ID']) }}">

                <button type="submit" class="btn primary">Finalizar cadastro</button>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const estadoSelect = document.getElementById('estado_residencia_id');
            const municipioSelect = document.getElementById('municipio_id');
            const urlTemplate = document.getElementById('municipios_fetch_url')?.value || '';
            let municipioSelecionado = '{{ old('municipio_id') }}';

            if (!estadoSelect || !municipioSelect || !urlTemplate) {
                return;
            }

            const resetMunicipios = (placeholder) => {
                municipioSelect.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                municipioSelect.appendChild(option);
            };

            const loadMunicipios = (estadoId) => {
                if (!estadoId) {
                    resetMunicipios('Selecione o estado');
                    return;
                }

                resetMunicipios('Carregando...');

                const url = urlTemplate.replace('STATE_ID', estadoId);
                fetch(url)
                    .then((response) => response.json())
                    .then((data) => {
                        resetMunicipios('Selecione');
                        data.forEach((municipio) => {
                            const option = document.createElement('option');
                            option.value = municipio.id;
                            option.textContent = municipio.nome;
                            if (municipioSelecionado && String(municipio.id) === String(municipioSelecionado)) {
                                option.selected = true;
                            }
                            municipioSelect.appendChild(option);
                        });
                    })
                    .catch(() => {
                        resetMunicipios('Erro ao carregar');
                    });
            };

            estadoSelect.addEventListener('change', (event) => {
                municipioSelecionado = '';
                loadMunicipios(event.target.value);
            });

            if (estadoSelect.value) {
                loadMunicipios(estadoSelect.value);
            } else {
                resetMunicipios('Selecione o estado');
            }
        });
    </script>
@endsection
