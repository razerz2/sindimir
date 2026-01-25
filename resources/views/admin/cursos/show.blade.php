@extends('admin.layouts.app')

@section('title', 'Detalhes do curso')

@section('content')
    <p><strong>Nome:</strong> {{ $curso->nome }}</p>
    <p><strong>Categoria:</strong> {{ $curso->categoria?->nome ?? '-' }}</p>
    <p><strong>Validade:</strong> {{ $curso->validade?->format('d/m/Y') ?? '-' }}</p>
    <p><strong>Limite de vagas:</strong> {{ $curso->limite_vagas }}</p>
    <p><strong>Status:</strong> {{ $curso->ativo ? 'Ativo' : 'Inativo' }}</p>
    <p><strong>Descrição:</strong> {{ $curso->descricao ?? '-' }}</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('admin.cursos.edit', $curso) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.cursos.index') }}">Voltar</a>
    </div>
@endsection
