@extends('layouts.public')

@section('title', 'Inscrição cancelada')

@section('content')
    <div class="card">
        <p>Sua inscrição foi cancelada com sucesso.</p>
    </div>

    <div class="card">
        <h2>Resumo do curso</h2>
        <p><strong>Curso:</strong> {{ $curso->nome }}</p>
        <p><strong>Datas:</strong> {{ $datas }}</p>
    </div>
@endsection
