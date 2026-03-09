@extends('admin.layouts.app')

@section('title', 'Editar evento')

@section('subtitle')
    Atualize os dados do evento selecionado.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'href' => route('admin.eventos.index'), 'icon' => 'calendar'],
        ['label' => 'Editar evento', 'icon' => 'edit', 'current' => true],
    ]" />
@endsection

@section('content')
    <form action="{{ route('admin.eventos.update', $evento) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.eventos.partials.form', ['evento' => $evento])
        <div class="flex justify-end gap-2">
            <x-admin.action as="a" variant="ghost" icon="arrow-left" href="{{ route('admin.eventos.index') }}">Voltar</x-admin.action>
            <x-admin.action variant="primary" icon="check" type="submit">Atualizar</x-admin.action>
        </div>
    </form>
@endsection
