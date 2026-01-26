@extends('layouts.public')

@section('title', 'Cursos disponíveis')

@section('content')
    <section class="section">
        <h1 class="section-title">Cursos disponíveis</h1>
        <p class="section-subtitle">
            Confira as turmas abertas e avance com sua inscrição em poucos passos.
        </p>
        <div class="grid">
            @forelse ($eventos as $evento)
                <div class="card">
                    <h3 style="margin-top: 0;">{{ $evento->curso?->nome ?? 'Curso' }}</h3>
                    <p class="muted"><strong>Evento:</strong> {{ $evento->numero_evento }}</p>
                    <p class="muted">
                        <strong>Período:</strong>
                        {{ $evento->data_inicio->format('d/m/Y') }}
                        a {{ $evento->data_fim->format('d/m/Y') }}
                    </p>
                    <p class="muted"><strong>Município:</strong> {{ $evento->municipio }}</p>
                    <p class="muted"><strong>Local:</strong> {{ $evento->local_realizacao }}</p>
                    <p class="muted">
                        <strong>Turno:</strong>
                        {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Não informado' }}
                    </p>
                    <div style="margin-top: 12px;">
                        <a class="btn primary" href="{{ route('public.cpf', ['evento_curso_id' => $evento->id]) }}">Inscrever-se</a>
                    </div>
                </div>
            @empty
                <div class="card">
                    <p>Nenhum curso disponível no momento.</p>
                </div>
            @endforelse
        </div>

        <div style="margin-top: 24px;">
            {{ $eventos->links() }}
        </div>
    </section>
@endsection
