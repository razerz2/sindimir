@extends('admin.layouts.app')

@section('title', 'Inscritos do evento')

@section('subtitle')
    @if ($evento->curso)
        {{ $evento->curso->nome }}@if ($evento->curso->trashed()) (removido) @endif
    @else
        Evento
    @endif
    - Nº {{ $evento->numero_evento }}
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'href' => route('admin.eventos.index'), 'icon' => 'calendar'],
        ['label' => 'Inscritos', 'icon' => 'user', 'current' => true],
    ]" />
@endsection

@section('content')
    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="page-actions">
        <div></div>
        <div class="flex flex-wrap gap-2">
            <x-admin.action variant="primary" icon="user-plus" type="button" data-enroll-open="inscrever-aluno-modal-{{ $evento->id }}">
                Inscrever aluno
            </x-admin.action>
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.eventos.show', $evento) }}">Voltar ao evento</x-admin.action>
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
                                @if ($matricula->aluno)
                                    <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.alunos.show', $matricula->aluno) }}">Ver aluno</x-admin.action>
                                @endif
                                @if ($isPendente)
                                    <form action="{{ route('admin.matriculas.confirmar', $matricula) }}" method="POST" style="display:inline"
                                        data-confirm="Deseja confirmar a inscrição deste aluno neste evento?"
                                        data-confirm-title="Confirmar inscrição"
                                        data-confirm-button="Confirmar inscrição">
                                        @csrf
                                        <x-admin.action variant="primary" icon="check" type="submit">Confirmar inscrição</x-admin.action>
                                    </form>
                                @endif
                                <form action="{{ route('admin.matriculas.cancelar', $matricula) }}" method="POST" style="display:inline"
                                    data-confirm="Escolha como remover a inscrição deste aluno."
                                    data-confirm-choice="remove-inscricao"
                                    data-confirm-title="Remover inscrição"
                                    data-confirm-secondary-button="Mover para espera"
                                    data-confirm-button="Confirmar"
                                    data-confirm-choice-input="acao"
                                    data-confirm-secondary-value="mover_espera"
                                    data-confirm-primary-value="confirmar">
                                    @csrf
                                    <input type="hidden" name="acao" value="mover_espera">
                                    <x-admin.action variant="danger" icon="trash" type="submit">Remover</x-admin.action>
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

    @include('admin.eventos.partials.inscrever-aluno-modal', ['evento' => $evento])
@endsection
