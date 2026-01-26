@extends('layouts.public')

@section('title', 'Matrícula confirmada')

@section('content')
    <div class="card">
        <p>Sua matrícula foi confirmada com sucesso.</p>
    </div>

    <div class="card">
        <h2>Detalhes do curso</h2>
        <p><strong>Curso:</strong> {{ $curso->nome }}</p>
        <p><strong>Datas:</strong> {{ $datas }}</p>
        <p><strong>Horario:</strong> {{ $horario }}</p>
        <p><strong>Carga horaria:</strong> {{ $cargaHoraria }}</p>
        <p><strong>Turno:</strong> {{ $turno }}</p>
        <p><strong>Local:</strong> {{ $evento->local_realizacao ?: 'Não informado' }}</p>
    </div>

    <div class="card">
        <h2>Status da matrícula</h2>
        <p><strong>Status:</strong> {{ ucfirst(strtolower($matricula->status->value)) }}</p>
        <p>
            <strong>Data de confirmação:</strong>
            {{ $matricula->data_confirmacao ? $matricula->data_confirmacao->format('d/m/Y H:i') : 'Não informada' }}
        </p>
    </div>

    @if ($matricula->status->value !== 'cancelada')
        <div class="card">
            <h2>Cancelar matrícula</h2>
            <p>Se precisar, você pode cancelar sua matrícula a qualquer momento.</p>
            <form method="POST" action="{{ route('public.matricula.cancelar', ['token' => request()->route('token')]) }}">
                @csrf
                <button type="submit" class="btn outline">Cancelar matrícula</button>
            </form>
        </div>
    @endif
@endsection
