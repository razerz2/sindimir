@extends('admin.layouts.app')

@section('title', 'Inscritos do evento')

@section('subtitle')
    {{ $evento->curso?->nome ?? 'Evento' }} · Nº {{ $evento->numero_evento }}
@endsection

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="page-actions">
        <div>
            <h3 class="section-title">Inscritos</h3>
            <p class="page-subtitle">Lista completa de inscrições e matrículas do evento.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('admin.eventos.show', $evento) }}">Voltar ao evento</a>
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
                        <td>{{ $matricula->aluno?->cpf ?? '-' }}</td>
                        <td>{{ $matricula->aluno?->email ?? '-' }}</td>
                        <td>
                            <span class="badge">
                                {{ $matricula->status?->value ? ucfirst(str_replace('_', ' ', $matricula->status->value)) : '-' }}
                            </span>
                        </td>
                        <td>{{ $matricula->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            <div class="table-actions">
                                @if ($matricula->aluno)
                                    <a class="btn btn-ghost" href="{{ route('admin.alunos.show', $matricula->aluno) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12c2.5-5 6.5-8 9.5-8s7 3 9.5 8c-2.5 5-6.5 8-9.5 8s-7-3-9.5-8z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9a3 3 0 100 6 3 3 0 000-6z" />
                                        </svg>
                                        <span>Ver aluno</span>
                                    </a>
                                @endif
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
                                        <span>Remover</span>
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
@endsection
