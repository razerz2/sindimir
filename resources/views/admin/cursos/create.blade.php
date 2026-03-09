@extends('admin.layouts.app')

@section('title', 'Novo curso')

@section('subtitle')
    Cadastre um novo curso no sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'href' => route('admin.cursos.index'), 'icon' => 'book'],
        ['label' => 'Novo curso', 'icon' => 'plus', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.cursos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.cursos.partials.form')
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.cursos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Salvar</x-admin.action>
        </div>
    </form>
@endsection
