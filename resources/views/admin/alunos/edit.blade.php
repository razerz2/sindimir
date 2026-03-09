@extends('admin.layouts.app')

@section('title', 'Editar aluno')

@section('subtitle')
    Atualize os dados cadastrais do aluno.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Alunos', 'href' => route('admin.alunos.index'), 'icon' => 'user'],
        ['label' => 'Editar aluno', 'icon' => 'edit', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.alunos.update', $aluno) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.alunos.partials.form', ['aluno' => $aluno])
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.alunos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Atualizar</x-admin.action>
        </div>
    </form>
@endsection
