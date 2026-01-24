@extends('admin.layouts.app')

@section('title', 'Editar evento')

@section('content')
    <form action="{{ route('admin.eventos.update', $evento) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.eventos.partials.form', ['evento' => $evento])
        <div class="flex justify-end gap-2">
            <a class="btn btn-ghost" href="{{ route('admin.eventos.index') }}">Voltar</a>
            <button class="btn btn-primary" type="submit">Atualizar</button>
        </div>
    </form>
@endsection
