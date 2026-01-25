@extends('admin.layouts.app')

@section('title', 'Detalhes do estado')

@section('content')
    <div class="space-y-3">
        <p><strong>Nome:</strong> {{ $estado->nome }}</p>
        <p><strong>UF:</strong> {{ $estado->uf }}</p>
        <p><strong>Municipios vinculados:</strong> {{ $estado->municipios_count ?? 0 }}</p>
        <p><strong>Status:</strong> {{ $estado->ativo ? 'Ativo' : 'Inativo' }}</p>
        <p><strong>Criado em:</strong> {{ $estado->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
        <p><strong>Atualizado em:</strong> {{ $estado->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('admin.catalogo.estados.edit', $estado) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.catalogo.estados.index') }}">Voltar</a>
    </div>
@endsection
