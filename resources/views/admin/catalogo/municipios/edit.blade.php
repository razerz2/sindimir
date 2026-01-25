@extends('admin.layouts.app')

@section('title', 'Editar municipio')

@section('content')
    <form action="{{ route('admin.catalogo.municipios.update', $municipio) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.catalogo.municipios.partials.form', ['municipio' => $municipio])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.show', $municipio) }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
