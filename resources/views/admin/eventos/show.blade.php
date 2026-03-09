@extends('admin.layouts.app')

@section('title', 'Detalhes do evento')

@section('subtitle')
    Visão geral, inscritos e lista de espera do evento.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'href' => route('admin.eventos.index'), 'icon' => 'calendar'],
        ['label' => 'Detalhes do evento', 'icon' => 'eye', 'current' => true],
    ]" />
@endsection

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="content-card">
        <p>
            <strong>Curso:</strong>
            @if ($evento->curso)
                {{ $evento->curso->nome }}@if ($evento->curso->trashed()) (removido) @endif
            @else
                -
            @endif
        </p>
        <p><strong>Número do evento:</strong> {{ $evento->numero_evento }}</p>
        <p><strong>Período:</strong> {{ $evento->data_inicio->format('d/m/Y') }} a {{ $evento->data_fim->format('d/m/Y') }}</p>
        @if ($evento->horario_inicio)
            <p><strong>Horário de início:</strong> {{ substr($evento->horario_inicio, 0, 5) }}</p>
        @endif
        <p><strong>Carga horária:</strong> {{ $evento->carga_horaria }}</p>
        <p><strong>Município:</strong> {{ $evento->municipio }}</p>
        <p><strong>Local de realização:</strong> {{ $evento->local_realizacao }}</p>
        <p><strong>Turno:</strong> {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : '-' }}</p>
        <p><strong>Status:</strong> {{ $evento->ativo ? 'Ativo' : 'Inativo' }}</p>
    </div>

    <div class="content-card mt-6">
        <div class="page-actions">
            <div>
                <h3 class="section-title">Resumo de vagas</h3>
                <p class="page-subtitle">Indicadores operacionais do evento.</p>
            </div>
        </div>
        <div class="grid">
            <div class="kpi-card">
                <div class="kpi-value">{{ $resumoVagas['total_vagas'] }}</div>
                <div class="kpi-label">Total de vagas</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">
                    <a href="{{ route('admin.eventos.inscritos', $evento) }}" class="underline">
                        {{ $resumoVagas['total_inscritos'] }}
                    </a>
                </div>
                <div class="kpi-label">Total de inscritos</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ $resumoVagas['total_lista_espera'] }}</div>
                <div class="kpi-label">Total na lista de espera</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ $resumoVagas['vagas_disponiveis'] }}</div>
                <div class="kpi-label">Vagas disponíveis</div>
            </div>
        </div>
    </div>

    <div class="content-card mt-6">
        <div class="page-actions">
            <div>
                <h3 class="section-title">Inscritos</h3>
                <p class="page-subtitle">Alunos inscritos neste evento.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-admin.action variant="primary" icon="user-plus" type="button" data-enroll-open="inscrever-aluno-modal-{{ $evento->id }}">
                    Inscrever aluno
                </x-admin.action>
                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.eventos.inscritos', $evento) }}">Ver lista completa</x-admin.action>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Nome do aluno</th>
                        <th>CPF</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Data da inscrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inscritos as $matricula)
                        @php($isPendente = $matricula->status?->value === 'pendente')
                        <tr>
                            <td>{{ $matricula->aluno?->nome_completo ?? '-' }}</td>
                            <td>{{ \App\Support\Cpf::format($matricula->aluno?->cpf) ?: '-' }}</td>
                            <td>{{ $matricula->aluno?->email ?? '-' }}</td>
                            <td>
                                <span class="badge">
                                    {{ $matricula->status?->value ? ucfirst(str_replace('_', ' ', $matricula->status->value)) : '-' }}
                                </span>
                            </td>
                            <td>{{ $matricula->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                <div class="table-actions">
                                    @if ($isPendente)
                                        <form
                                            action="{{ route('admin.matriculas.confirmar', $matricula) }}"
                                            method="POST"
                                            style="display:inline"
                                            data-confirm="Deseja confirmar a inscrição deste aluno neste evento?"
                                            data-confirm-title="Confirmar inscrição"
                                            data-confirm-button="Confirmar inscrição"
                                        >
                                            @csrf
                                            <x-admin.action variant="primary" icon="check" type="submit">Confirmar inscrição</x-admin.action>
                                        </form>
                                    @endif
                                    <form
                                        action="{{ route('admin.matriculas.cancelar', $matricula) }}"
                                        method="POST"
                                        style="display:inline"
                                        data-confirm="Escolha como remover a inscrição deste aluno."
                                        data-confirm-choice="remove-inscricao"
                                        data-confirm-title="Remover inscrição"
                                        data-confirm-secondary-button="Mover para espera"
                                        data-confirm-button="Confirmar"
                                        data-confirm-choice-input="acao"
                                        data-confirm-secondary-value="mover_espera"
                                        data-confirm-primary-value="confirmar"
                                    >
                                        @csrf
                                        <input type="hidden" name="acao" value="mover_espera">
                                        <x-admin.action variant="danger" icon="trash" type="submit">Remover inscrição</x-admin.action>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>Nenhuma inscrição encontrada.</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-card mt-6">
        <div class="page-actions">
            <div>
                <h3 class="section-title">Lista de espera</h3>
                <p class="page-subtitle">Visualização dos alunos aguardando vaga.</p>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Nome do aluno</th>
                        <th>CPF</th>
                        <th>Data de entrada</th>
                        <th>Posição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listaEspera as $item)
                        @php($isAguardando = $item->status?->value === 'aguardando')
                        <tr>
                            <td>{{ $item->aluno?->nome_completo ?? '-' }}</td>
                            <td>{{ \App\Support\Cpf::format($item->aluno?->cpf) ?: '-' }}</td>
                            <td>{{ $item->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $item->posicao ?? '-' }}</td>
                            <td>
                                <div class="table-actions">
                                    <form action="{{ route('admin.lista-espera.subir', $item) }}" method="POST" style="display:inline">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit" @if (! $isAguardando || $loop->first) disabled @endif>
                                            <span>Subir</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.lista-espera.descer', $item) }}" method="POST" style="display:inline">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit" @if (! $isAguardando || $loop->last) disabled @endif>
                                            <span>Descer</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.lista-espera.inscrever', $item) }}" method="POST" style="display:inline">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit" @if (! $isAguardando) disabled @endif>
                                            <span>Inscrever</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.lista-espera.remover', $item) }}" method="POST" style="display:inline"
                                        data-confirm="Deseja remover este aluno da lista de espera do evento?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit" @if (! $isAguardando) disabled @endif>
                                            <span>Remover</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>Nenhum aluno na lista de espera.</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <x-admin.action as="a" variant="primary" icon="edit" href="{{ route('admin.eventos.edit', $evento) }}">Editar</x-admin.action>
        <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.eventos.index') }}">Voltar</x-admin.action>
    </div>

    @include('admin.eventos.partials.inscrever-aluno-modal', ['evento' => $evento])
@endsection
