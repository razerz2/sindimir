@extends('admin.layouts.app')

@section('title', 'Editar curso')

@section('subtitle')
    Atualize os dados do curso selecionado.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'href' => route('admin.cursos.index'), 'icon' => 'book'],
        ['label' => 'Editar curso', 'icon' => 'edit', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.cursos.update', $curso) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.cursos.partials.form', ['curso' => $curso])
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.cursos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Atualizar</x-admin.action>
        </div>
    </form>
@endsection
