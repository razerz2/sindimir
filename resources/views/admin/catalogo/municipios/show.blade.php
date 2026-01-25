@extends('admin.layouts.app')

@section('title', 'Detalhes do municipio')

@section('content')
    <div class="space-y-3">
        <p><strong>Nome:</strong> {{ $municipio->nome }}</p>
        <p><strong>Estado:</strong> {{ $municipio->estado?->nome }} ({{ $municipio->estado?->uf }})</p>
        <p><strong>Status:</strong> {{ $municipio->ativo ? 'Ativo' : 'Inativo' }}</p>
        <p><strong>Criado em:</strong> {{ $municipio->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
        <p><strong>Atualizado em:</strong> {{ $municipio->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('admin.catalogo.municipios.edit', $municipio) }}">Editar</a>
        <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.index') }}">Voltar</a>
    </div>
@endsection
