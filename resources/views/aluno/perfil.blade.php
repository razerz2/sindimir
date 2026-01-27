@extends('aluno.layouts.app')

@section('title', 'Perfil')

@section('content')
    <p>Gerencie seus dados cadastrais.</p>

    @if (session('status'))
        <div class="card" style="margin: 16px 0;">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <form action="{{ route('aluno.perfil.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')
        @include('aluno.partials.form', compact('aluno', 'deficiencias', 'selects', 'estados'))
        <button type="submit" class="btn primary">Salvar dados</button>
    </form>
@endsection
