@extends('admin.layouts.app')

@section('title', 'Detalhes do curso')

@section('subtitle')
    Visualização das informações principais do curso.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'href' => route('admin.cursos.index'), 'icon' => 'book'],
        ['label' => 'Detalhes do curso', 'icon' => 'eye', 'current' => true],
    ]" />
@endsection

@section('content')
    <p><strong>Nome:</strong> {{ $curso->nome }}</p>
    <p><strong>Categoria:</strong> {{ $curso->categoria?->nome ?? '-' }}</p>
    <p><strong>Validade:</strong> {{ $curso->validade?->format('d/m/Y') ?? '-' }}</p>
    <p><strong>Limite de vagas:</strong> {{ $curso->limite_vagas }}</p>
    <p><strong>Status:</strong> {{ $curso->ativo ? 'Ativo' : 'Inativo' }}</p>
    <p><strong>Descrição:</strong> {{ $curso->descricao ?? '-' }}</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <x-admin.action as="a" variant="primary" icon="edit" href="{{ route('admin.cursos.edit', $curso) }}">Editar</x-admin.action>
        <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.cursos.index') }}">Voltar</x-admin.action>
    </div>
@endsection
