@extends('admin.layouts.app')

@section('title', 'Editar aluno')

@section('content')
    <form action="{{ route('admin.alunos.update', $aluno) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.alunos.partials.form', ['aluno' => $aluno])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.alunos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Atualizar</button>
        </div>
    </form>
@endsection
