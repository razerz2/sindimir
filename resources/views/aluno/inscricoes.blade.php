@extends('aluno.layouts.app')

@section('title', 'Inscrição em cursos')
@section('subtitle', 'Confira as turmas abertas e faça sua inscrição em poucos cliques.')

@section('content')
    @if (session('status'))
        <div class="alert alert-card">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    @if ($eventosPreferidos->isNotEmpty())
        <h3 class="section-title">Sugestões para você</h3>
        <div class="grid">
            @foreach ($eventosPreferidos as $evento)
                @php
                    $matricula = $matriculas->get($evento->id);
                    $lista = $listaEspera->get($evento->id);
                    $statusLabel = null;
                    $statusClass = 'neutral';
                    if ($matricula) {
                        $statusLabel = ucfirst($matricula->status?->value ?? 'matricula');
                        $statusClass = match ($matricula->status?->value) {
                            'confirmada' => 'success',
                            'pendente' => 'warning',
                            'cancelada' => 'neutral',
                            'expirada' => 'danger',
                            default => 'neutral',
                        };
                    } elseif ($lista) {
                        $statusLabel = 'Lista de espera';
                        $statusClass = $lista->status?->value === 'chamado' ? 'info' : 'warning';
                    }
                @endphp
                <div class="card">
                    <div class="flex items-start justify-between gap-3">
                        <h3 style="margin: 0;">{{ $evento->curso?->nome ?? 'Curso' }}</h3>
                        @if (! empty($preferenciasIds) && $evento->curso?->categoria_id && in_array($evento->curso->categoria_id, $preferenciasIds, true))
                            <span class="badge info">Recomendado</span>
                        @endif
                    </div>
                    <p class="muted"><strong>Evento:</strong> {{ $evento->numero_evento }}</p>
                    <p class="muted">
                        <strong>Periodo:</strong>
                        @if ($evento->data_inicio)
                            {{ $evento->data_inicio->format('d/m/Y') }}
                            @if ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio))
                                a {{ $evento->data_fim->format('d/m/Y') }}
                            @endif
                        @else
                            Data ainda não informada.
                        @endif
                    </p>
                    <p class="muted"><strong>Municipio:</strong> {{ $evento->municipio ?? 'Nao informado' }}</p>
                    <p class="muted"><strong>Local:</strong> {{ $evento->local_realizacao ?? 'Nao informado' }}</p>
                    <p class="muted">
                        <strong>Turno:</strong>
                        {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Nao informado' }}
                    </p>
                    <div class="flex flex-wrap items-center justify-between gap-3" style="margin-top: 12px;">
                        @if ($statusLabel)
                            <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        @endif
                        @if ($matricula || $lista)
                            <button class="btn btn-ghost" disabled>Inscricao registrada</button>
                        @else
                            <form action="{{ route('aluno.inscricoes.store', ['eventoCurso' => $evento->id]) }}" method="POST">
                                @csrf
                                <button class="btn btn-primary" type="submit">Inscrever-se</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <h3 class="section-title">Cursos disponíveis</h3>
    <div class="grid">
        @forelse ($eventos as $evento)
            @php
                $matricula = $matriculas->get($evento->id);
                $lista = $listaEspera->get($evento->id);
                $statusLabel = null;
                $statusClass = 'neutral';
                if ($matricula) {
                    $statusLabel = ucfirst($matricula->status?->value ?? 'matricula');
                    $statusClass = match ($matricula->status?->value) {
                        'confirmada' => 'success',
                        'pendente' => 'warning',
                        'cancelada' => 'neutral',
                        'expirada' => 'danger',
                        default => 'neutral',
                    };
                } elseif ($lista) {
                    $statusLabel = 'Lista de espera';
                    $statusClass = $lista->status?->value === 'chamado' ? 'info' : 'warning';
                }
            @endphp
            <div class="card">
                <div class="flex items-start justify-between gap-3">
                    <h3 style="margin: 0;">{{ $evento->curso?->nome ?? 'Curso' }}</h3>
                    @if (! empty($preferenciasIds) && $evento->curso?->categoria_id && in_array($evento->curso->categoria_id, $preferenciasIds, true))
                        <span class="badge info">Recomendado</span>
                    @endif
                </div>
                <p class="muted"><strong>Evento:</strong> {{ $evento->numero_evento }}</p>
                <p class="muted">
                    <strong>Periodo:</strong>
                    @if ($evento->data_inicio)
                        {{ $evento->data_inicio->format('d/m/Y') }}
                        @if ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio))
                            a {{ $evento->data_fim->format('d/m/Y') }}
                        @endif
                    @else
                        Data ainda não informada.
                    @endif
                </p>
                <p class="muted"><strong>Municipio:</strong> {{ $evento->municipio ?? 'Nao informado' }}</p>
                <p class="muted"><strong>Local:</strong> {{ $evento->local_realizacao ?? 'Nao informado' }}</p>
                <p class="muted">
                    <strong>Turno:</strong>
                    {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Nao informado' }}
                </p>
                <div class="flex flex-wrap items-center justify-between gap-3" style="margin-top: 12px;">
                    @if ($statusLabel)
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    @endif
                    @if ($matricula || $lista)
                        <button class="btn btn-ghost" disabled>Inscricao registrada</button>
                    @else
                        <form action="{{ route('aluno.inscricoes.store', ['eventoCurso' => $evento->id]) }}" method="POST">
                            @csrf
                            <button class="btn btn-primary" type="submit">Inscrever-se</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="card">
                <p>Nenhum curso disponivel no momento.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $eventos->links() }}
    </div>
@endsection
