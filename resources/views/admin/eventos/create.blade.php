@extends('admin.layouts.app')

@section('title', 'Novo evento')

@section('subtitle')
    Cadastre um novo evento vinculado a um curso.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'href' => route('admin.eventos.index'), 'icon' => 'calendar'],
        ['label' => 'Novo evento', 'icon' => 'plus', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.eventos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.eventos.partials.form')
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.eventos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Salvar</x-admin.action>
        </div>
    </form>
@endsection
