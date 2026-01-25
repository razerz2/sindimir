@extends('admin.layouts.app')

@section('title', 'Editar estado')

@section('content')
    <form action="{{ route('admin.catalogo.estados.update', $estado) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.catalogo.estados.partials.form', ['estado' => $estado])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.estados.show', $estado) }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
