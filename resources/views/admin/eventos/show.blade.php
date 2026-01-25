@extends('admin.layouts.app')

@section('title', 'Detalhes do evento')

@section('content')
    <p><strong>Curso:</strong> {{ $evento->curso?->nome ?? '-' }}</p>
    <p><strong>Número do evento:</strong> {{ $evento->numero_evento }}</p>
    <p><strong>Período:</strong> {{ $evento->data_inicio->format('d/m/Y') }} a {{ $evento->data_fim->format('d/m/Y') }}</p>
    <p><strong>Carga horária:</strong> {{ $evento->carga_horaria }}</p>
    <p><strong>Município:</strong> {{ $evento->municipio }}</p>
    <p><strong>Local de realização:</strong> {{ $evento->local_realizacao }}</p>
    <p><strong>Turno:</strong> {{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : '-' }}</p>
    <p><strong>Status:</strong> {{ $evento->ativo ? 'Ativo' : 'Inativo' }}</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('admin.eventos.edit', $evento) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.eventos.index') }}">Voltar</a>
    </div>
@endsection
