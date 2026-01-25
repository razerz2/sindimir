@extends('admin.layouts.app')

@section('title', 'Novo municipio')

@section('content')
    <form action="{{ route('admin.catalogo.municipios.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.catalogo.municipios.partials.form')
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
