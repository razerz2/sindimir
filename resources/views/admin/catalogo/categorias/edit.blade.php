@extends('admin.layouts.app')

@section('title', 'Editar categoria')

@section('content')
    <form action="{{ route('admin.catalogo.categorias.update', $categoria) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.catalogo.categorias.partials.form', ['categoria' => $categoria])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.categorias.show', $categoria) }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
