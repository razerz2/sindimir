@extends('admin.layouts.app')

@section('title', 'Nova categoria')

@section('content')
    <form action="{{ route('admin.catalogo.categorias.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.catalogo.categorias.partials.form')
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.categorias.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
