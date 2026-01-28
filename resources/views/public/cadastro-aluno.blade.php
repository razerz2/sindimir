@extends('layouts.public')

@section('title', 'Cadastro de aluno')

@section('content')
    <section class="section">
        @php
            $temEventoCurso = old('evento_curso_id', request('evento_curso_id')) !== null
                && old('evento_curso_id', request('evento_curso_id')) !== '';
        @endphp
        <h1 class="section-title">
            {{ $temEventoCurso ? 'Inscrição do aluno' : 'Cadastro de aluno' }}
        </h1>
        <p class="section-subtitle">
            {{ $temEventoCurso
                ? 'Preencha os dados abaixo para concluir sua inscrição.'
                : 'Preencha os dados abaixo para concluir seu cadastro e seguir com a inscrição.' }}
        </p>
        @if (session('status'))
            <div class="card" style="margin-bottom: 16px;">
                <p>{{ session('status') }}</p>
            </div>
        @endif
        <div class="content-card card">
            <form action="{{ route('public.cadastro.store') }}" method="POST" class="space-y-8 form">
                @csrf
                <input type="hidden" name="evento_curso_id" value="{{ old('evento_curso_id', request('evento_curso_id')) }}">
                <div>
                    <h3 class="text-base font-semibold">Identificacao e vinculo</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="cpf" class="text-sm font-semibold text-[var(--content-text)]">CPF</label>
                            <input
                                id="cpf"
                                name="cpf"
                                type="text"
                                value="{{ \App\Support\Cpf::format(old('cpf')) }}"
                                placeholder="000.000.000-00"
                                inputmode="numeric"
                                data-mask="cpf"
                                required
                                class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                            >
                            @error('cpf')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="nome_completo" class="text-sm font-semibold text-[var(--content-text)]">Nome completo</label>
                            <input id="nome_completo" name="nome_completo" type="text" value="{{ old('nome_completo') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('nome_completo')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="data_nascimento" class="text-sm font-semibold text-[var(--content-text)]">Data de nascimento</label>
                            <input id="data_nascimento" name="data_nascimento" type="date" value="{{ old('data_nascimento') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('data_nascimento')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="sexo" class="text-sm font-semibold text-[var(--content-text)]">Sexo</label>
                            <select id="sexo" name="sexo" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Selecione</option>
                                @foreach ($selects['sexo'] as $sexo)
                                    <option value="{{ $sexo->value }}" {{ old('sexo') == $sexo->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $sexo->value)) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sexo')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Contato</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2">
                            <label for="email" class="text-sm font-semibold text-[var(--content-text)]">E-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('email')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="celular" class="text-sm font-semibold text-[var(--content-text)]">Celular</label>
                            <input
                                id="celular"
                                name="celular"
                                type="text"
                                value="{{ \App\Support\Phone::format(old('celular')) }}"
                                placeholder="(00) 00000-0000"
                                inputmode="numeric"
                                data-mask="phone"
                                required
                                class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40"
                            >
                            @error('celular')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold">Endereco</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-2 lg:col-span-2">
                            <label for="endereco" class="text-sm font-semibold text-[var(--content-text)]">Endereco</label>
                            <input id="endereco" name="endereco" type="text" value="{{ old('endereco') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('endereco')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="bairro" class="text-sm font-semibold text-[var(--content-text)]">Bairro</label>
                            <input id="bairro" name="bairro" type="text" value="{{ old('bairro') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('bairro')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
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
                            @error('estado_residencia_id')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="municipio_id" class="text-sm font-semibold text-[var(--content-text)]">Municipio</label>
                            <select id="municipio_id" name="municipio_id" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                                <option value="">Selecione o estado</option>
                            </select>
                            @error('municipio_id')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="cep" class="text-sm font-semibold text-[var(--content-text)]">CEP</label>
                            <input id="cep" name="cep" type="text" value="{{ old('cep') }}" required class="input w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40">
                            @error('cep')
                                <p class="text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <input type="hidden" id="municipios_fetch_url" value="{{ route('public.catalogo.estados.municipios', ['estado' => 'STATE_ID']) }}">

                <button type="submit" class="btn primary">
                    {{ $temEventoCurso ? 'Finalizar inscrição' : 'Finalizar cadastro' }}
                </button>
            </form>
        </div>
    </section>

    @include('partials.input-masks')

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
