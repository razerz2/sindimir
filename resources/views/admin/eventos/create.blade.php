@extends('admin.layouts.app')

@section('title', 'Novo evento')

@section('content')
    <form action="{{ route('admin.eventos.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('admin.eventos.partials.form')
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.eventos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Salvar</button>
        </div>
    </form>
@endsection
