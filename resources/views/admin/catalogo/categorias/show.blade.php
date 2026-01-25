@extends('admin.layouts.app')

@section('title', 'Detalhes da categoria')

@section('content')
    <div class="space-y-3">
        <p><strong>Nome:</strong> {{ $categoria->nome }}</p>
        <p><strong>Slug:</strong> {{ $categoria->slug }}</p>
        <p><strong>Status:</strong> {{ $categoria->ativo ? 'Ativo' : 'Inativo' }}</p>
        <p><strong>Criado em:</strong> {{ $categoria->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
        <p><strong>Atualizado em:</strong> {{ $categoria->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('admin.catalogo.categorias.edit', $categoria) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.catalogo.categorias.index') }}">Voltar</a>
    </div>
@endsection
