@extends('aluno.layouts.app')

@section('title', 'Perfil')
@section('subtitle', 'Mantenha seus dados atualizados para facilitar inscrições e comunicados.')

@section('content')
    @if (session('status'))
        <div class="alert alert-card">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <form action="{{ route('aluno.perfil.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')
        @include('aluno.partials.form', compact('aluno', 'deficiencias', 'selects', 'estados'))
        <button type="submit" class="btn btn-primary">Salvar dados</button>
    </form>
@endsection
