@extends('admin.layouts.app')

@section('title', 'Novo curso')

@section('content')
    <form action="{{ route('admin.cursos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.cursos.partials.form')
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.cursos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
