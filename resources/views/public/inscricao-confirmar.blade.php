@extends('layouts.public')

@section('title', 'Confirmar inscrição')

@section('content')
    <div class="card">
        <p>Olá, {{ $primeiroNome }}!</p>
        <p>Confira os detalhes do curso e confirme sua inscrição.</p>
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
        <h2>Confirmar inscrição</h2>
        <p>Deseja confirmar sua inscrição neste curso?</p>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <form method="POST" action="{{ route('public.inscricao.confirmar.sim', ['token' => $token]) }}">
                @csrf
                <button type="submit" class="btn primary">Sim, confirmar</button>
            </form>
            <form method="POST" action="{{ route('public.inscricao.confirmar.nao', ['token' => $token]) }}">
                @csrf
                <button type="submit" class="btn outline">Não, cancelar</button>
            </form>
        </div>
    </div>
@endsection
