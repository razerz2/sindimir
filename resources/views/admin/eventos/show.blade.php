@extends('admin.layouts.app')

@section('title', 'Detalhes do evento')

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    <p><strong>Curso:</strong> {{ $evento->curso?->nome ?? '-' }}</p>
    <p><strong>Número do evento:</strong> {{ $evento->numero_evento }}</p>
    <p><strong>Período:</strong> {{ $evento->data_inicio->format('d/m/Y') }} a {{ $evento->data_fim->format('d/m/Y') }}</p>
    @if ($evento->horario_inicio)
        <p><strong>Horario de inicio:</strong> {{ substr($evento->horario_inicio, 0, 5) }}</p>
    @endif
    <p><strong>Carga horária:</strong> {{ $evento->carga_horaria }}</p>
    <p><strong>Município:</strong> {{ $evento->municipio }}</p>
    <p><strong>Local de realização:</strong> {{ $evento->local_realizacao }}</p>
    <p><strong>Turno:</strong> {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : '-' }}</p>
    <p><strong>Status:</strong> {{ $evento->ativo ? 'Ativo' : 'Inativo' }}</p>

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
            <a class="btn btn-ghost" href="{{ route('admin.eventos.inscritos', $evento) }}">Ver lista completa</a>
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
                                <form action="{{ route('admin.matriculas.cancelar', $matricula) }}" method="POST" style="display:inline"
                                    data-confirm="Ao remover a inscrição, o aluno perderá a vaga e será movido para o final da lista de espera. Deseja continuar?">
                                        @csrf
                                        <button class="btn btn-danger" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 7l1 12.5A2.5 2.5 0 0 0 8.5 22h7a2.5 2.5 0 0 0 2.5-2.5L19 7" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1V7" />
                                            </svg>
                                            <span>Remover inscrição</span>
                                        </button>
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
        <a class="btn btn-primary" href="{{ route('admin.eventos.edit', $evento) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.eventos.index') }}">Voltar</a>
    </div>
@endsection
