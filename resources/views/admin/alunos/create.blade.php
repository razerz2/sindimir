@extends('admin.layouts.app')

@section('title', 'Novo aluno')

@section('content')
    <form action="{{ route('admin.alunos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.alunos.partials.form')
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.alunos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
