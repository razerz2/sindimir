@extends('admin.layouts.app')

@section('title', 'Novo aluno')

@section('subtitle')
    Cadastre um novo aluno no sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Alunos', 'href' => route('admin.alunos.index'), 'icon' => 'user'],
        ['label' => 'Novo aluno', 'icon' => 'plus', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.alunos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.alunos.partials.form')
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.alunos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Salvar</x-admin.action>
        </div>
    </form>
@endsection
