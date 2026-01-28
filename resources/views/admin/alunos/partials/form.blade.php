@php
    $aluno = $aluno ?? null;
    $usuarioOptions = $usuarios->map(fn ($usuario) => [
        'value' => $usuario->id,
        'label' => "{$usuario->display_name} ({$usuario->email})",
    ])->all();
    $sexoOptions = collect($selects['sexo'])->map(fn ($sexo) => [
        'value' => $sexo->value,
        'label' => ucfirst(str_replace('_', ' ', $sexo->value)),
    ])->all();
    $estadoCivilOptions = collect($selects['estado_civil'])->map(fn ($estado) => [
        'value' => $estado->value,
        'label' => ucfirst(str_replace('_', ' ', $estado->value)),
    ])->all();
    $racaCorOptions = collect($selects['raca_cor'])->map(fn ($raca) => [
        'value' => $raca->value,
        'label' => ucfirst(str_replace('_', ' ', $raca->value)),
    ])->all();
    $possuiDeficienciaOptions = collect($selects['possui_deficiencia'])->map(fn ($opcao) => [
        'value' => $opcao->value,
        'label' => ucfirst(str_replace('_', ' ', $opcao->value)),
    ])->all();
    $escolaridadeOptions = collect($selects['escolaridade'])->map(fn ($escolaridade) => [
        'value' => $escolaridade->value,
        'label' => ucfirst(str_replace('_', ' ', $escolaridade->value)),
    ])->all();
    $rendaOptions = collect($selects['renda_familiar'])->map(fn ($renda) => [
        'value' => $renda->value,
        'label' => ucfirst(str_replace('_', ' ', $renda->value)),
    ])->all();
    $situacaoOptions = collect($selects['situacao_participante'])->map(fn ($situacao) => [
        'value' => $situacao->value,
        'label' => ucfirst(str_replace('_', ' ', $situacao->value)),
    ])->all();
    $tipoEntidadeOptions = collect($selects['tipo_entidade_origem'])->map(fn ($tipo) => [
        'value' => $tipo->value,
        'label' => ucfirst(str_replace('_', ' ', $tipo->value)),
    ])->all();
    $simNaoOptions = [
        ['value' => '1', 'label' => 'Sim'],
        ['value' => '0', 'label' => 'Nao'],
    ];
    $booleanToOption = fn ($value) => is_null($value) ? null : ($value ? '1' : '0');
    $estadoOptions = $estados->map(fn ($estado) => [
        'value' => $estado->id,
        'label' => "{$estado->nome} ({$estado->uf})",
    ])->all();
    $estadoSelecionado = old('estado_residencia_id', $aluno->estado_residencia_id ?? '');
    $municipioSelecionado = old('municipio_id', $aluno->municipio_id ?? '');
@endphp

<div class="space-y-8">
    <div>
        <h3 class="text-base font-semibold">Identificacao e vinculo</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-admin.select
                id="user_id"
                name="user_id"
                label="Usuario vinculado"
                :options="$usuarioOptions"
                :selected="$aluno->user_id ?? null"
                placeholder="Sem vinculo"
                wrapper-class="lg:col-span-2"
            />
            <x-admin.input
                id="cpf"
                name="cpf"
                label="CPF"
                :value="\App\Support\Cpf::format($aluno->cpf ?? '')"
                placeholder="000.000.000-00"
                inputmode="numeric"
                data-mask="cpf"
                required
            />
            <x-admin.input
                id="nome_completo"
                name="nome_completo"
                label="Nome completo"
                :value="$aluno->nome_completo ?? ''"
                required
                wrapper-class="lg:col-span-2"
            />
            <x-admin.input
                id="nome_social"
                name="nome_social"
                label="Nome social"
                :value="$aluno->nome_social ?? ''"
            />
            <x-admin.input
                id="data_nascimento"
                name="data_nascimento"
                label="Data de nascimento"
                type="date"
                :value="isset($aluno) && $aluno->data_nascimento ? $aluno->data_nascimento->format('Y-m-d') : ''"
                required
            />
            <x-admin.select
                id="sexo"
                name="sexo"
                label="Sexo"
                :options="$sexoOptions"
                :selected="$aluno->sexo?->value ?? null"
                placeholder="Selecione"
                required
            />
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold">Naturalidade e familia</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-admin.input
                id="naturalidade"
                name="naturalidade"
                label="Naturalidade"
                :value="$aluno->naturalidade ?? ''"
            />
            <x-admin.input
                id="nacionalidade"
                name="nacionalidade"
                label="Nacionalidade"
                :value="$aluno->nacionalidade ?? ''"
            />
            <x-admin.input
                id="uf_naturalidade"
                name="uf_naturalidade"
                label="UF (naturalidade)"
                :value="$aluno->uf_naturalidade ?? ''"
                placeholder="UF"
            />
            <x-admin.input
                id="nome_pai"
                name="nome_pai"
                label="Nome do pai"
                :value="$aluno->nome_pai ?? ''"
                wrapper-class="lg:col-span-2"
            />
            <x-admin.input
                id="nome_mae"
                name="nome_mae"
                label="Nome da mae"
                :value="$aluno->nome_mae ?? ''"
                wrapper-class="lg:col-span-2"
            />
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold">Endereco e contato</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-admin.input
                id="endereco"
                name="endereco"
                label="Endereco"
                :value="$aluno->endereco ?? ''"
                wrapper-class="lg:col-span-2"
                required
            />
            <x-admin.input
                id="bairro"
                name="bairro"
                label="Bairro"
                :value="$aluno->bairro ?? ''"
                required
            />
            <x-admin.select
                id="estado_residencia_id"
                name="estado_residencia_id"
                label="UF (residencia)"
                :options="$estadoOptions"
                :selected="$estadoSelecionado"
                placeholder="Selecione"
                required
            />
            <x-admin.select
                id="municipio_id"
                name="municipio_id"
                label="Municipio"
                :options="[]"
                :selected="$municipioSelecionado"
                placeholder="Selecione o estado"
                required
            />
            <x-admin.input
                id="cep"
                name="cep"
                label="CEP"
                :value="$aluno->cep ?? ''"
                required
            />
            <x-admin.input
                id="email"
                name="email"
                label="E-mail"
                type="email"
                :value="$aluno->email ?? ''"
                required
            />
            <x-admin.input
                id="celular"
                name="celular"
                label="Celular"
                :value="\App\Support\Phone::format($aluno->celular ?? '')"
                placeholder="(00) 00000-0000"
                inputmode="numeric"
                data-mask="phone"
                required
            />
            <x-admin.input
                id="telefone"
                name="telefone"
                label="Telefone"
                :value="\App\Support\Phone::format($aluno->telefone ?? '')"
                placeholder="(00) 0000-0000"
                inputmode="numeric"
                data-mask="phone"
            />
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold">Informacoes socioeconomicas</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-admin.select
                id="estado_civil"
                name="estado_civil"
                label="Estado civil"
                :options="$estadoCivilOptions"
                :selected="$aluno->estado_civil?->value ?? null"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="raca_cor"
                name="raca_cor"
                label="Raca/Cor"
                :options="$racaCorOptions"
                :selected="$aluno->raca_cor?->value ?? null"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="escolaridade"
                name="escolaridade"
                label="Escolaridade"
                :options="$escolaridadeOptions"
                :selected="$aluno->escolaridade?->value ?? null"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="renda_familiar"
                name="renda_familiar"
                label="Renda familiar"
                :options="$rendaOptions"
                :selected="$aluno->renda_familiar?->value ?? null"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="estuda"
                name="estuda"
                label="Estuda?"
                :options="$simNaoOptions"
                :selected="$booleanToOption($aluno->estuda ?? null)"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="trabalha"
                name="trabalha"
                label="Trabalha?"
                :options="$simNaoOptions"
                :selected="$booleanToOption($aluno->trabalha ?? null)"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="situacao_participante"
                name="situacao_participante"
                label="Situacao do participante"
                :options="$situacaoOptions"
                :selected="$aluno->situacao_participante?->value ?? null"
                placeholder="Nao informado"
                wrapper-class="lg:col-span-2"
            />
            <x-admin.select
                id="tipo_entidade_origem"
                name="tipo_entidade_origem"
                label="Tipo de entidade de origem"
                :options="$tipoEntidadeOptions"
                :selected="$aluno->tipo_entidade_origem?->value ?? null"
                placeholder="Nao informado"
                wrapper-class="lg:col-span-2"
            />
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold">Programas e beneficios</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <x-admin.input
                id="numero_cadastro_unico"
                name="numero_cadastro_unico"
                label="Numero do Cadastro Unico"
                :value="$aluno->numero_cadastro_unico ?? ''"
            />
            <x-admin.select
                id="recebe_bolsa_familia"
                name="recebe_bolsa_familia"
                label="Recebe Bolsa Familia?"
                :options="$simNaoOptions"
                :selected="$booleanToOption($aluno->recebe_bolsa_familia ?? null)"
                placeholder="Nao informado"
            />
            <x-admin.select
                id="responsavel_menor"
                name="responsavel_menor"
                label="Responsavel por menor?"
                :options="$simNaoOptions"
                :selected="$booleanToOption($aluno->responsavel_menor ?? null)"
                placeholder="Nao informado"
            />
            <x-admin.input
                id="idade_menor_mais_novo"
                name="idade_menor_mais_novo"
                label="Idade do menor mais novo"
                type="number"
                :value="$aluno->idade_menor_mais_novo ?? ''"
            />
            <x-admin.select
                id="tem_com_quem_deixar_menores"
                name="tem_com_quem_deixar_menores"
                label="Tem com quem deixar os menores?"
                :options="$simNaoOptions"
                :selected="$booleanToOption($aluno->tem_com_quem_deixar_menores ?? null)"
                placeholder="Nao informado"
                wrapper-class="lg:col-span-2"
            />
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold">Deficiencias</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin.select
                id="possui_deficiencia"
                name="possui_deficiencia"
                label="Possui deficiencia?"
                :options="$possuiDeficienciaOptions"
                :selected="$aluno->possui_deficiencia?->value ?? null"
                placeholder="Nao informado"
            />
            <x-admin.input
                id="deficiencia_descricao"
                name="deficiencia_descricao"
                label="Descricao adicional"
                :value="old('deficiencia_descricao', $aluno->deficiencia_descricao ?? '')"
                wrapper-class="md:col-span-2"
            />
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
                            {{ in_array($deficiencia->id, old('deficiencias', $aluno?->deficiencias?->pluck('id')->all() ?? []), true) ? 'checked' : '' }}
                        >
                        <span>{{ $deficiencia->nome }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="municipios_fetch_url" value="{{ route('admin.catalogo.estados.municipios', ['estado' => 'STATE_ID']) }}">
@include('partials.input-masks')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const estadoSelect = document.getElementById('estado_residencia_id');
        const municipioSelect = document.getElementById('municipio_id');
        const urlTemplate = document.getElementById('municipios_fetch_url')?.value || '';
        let municipioSelecionado = '{{ $municipioSelecionado }}';

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
