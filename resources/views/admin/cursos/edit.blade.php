@extends('admin.layouts.app')

@section('title', 'Editar curso')

@section('content')
    <form action="{{ route('admin.cursos.update', $curso) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.cursos.partials.form', ['curso' => $curso])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.cursos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Atualizar</button>
        </div>
    </form>
@endsection
