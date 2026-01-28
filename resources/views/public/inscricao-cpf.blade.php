@extends('layouts.public')

@section('title', 'Inscricao por CPF')

@section('content')
    <section class="section">
        <h1 class="section-title">Inscricao por CPF</h1>
        <p class="section-subtitle">
            Informe seu CPF para localizar sua inscricao ou iniciar um novo cadastro.
        </p>

        @if (session('status'))
            <div class="card">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        <div class="card">
            <form class="form" action="{{ route('public.cpf.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="evento_curso_id" value="{{ old('evento_curso_id', request('evento_curso_id')) }}">
                <div class="field">
                    <label for="cpf">CPF</label>
                    <input
                        class="input"
                        id="cpf"
                        name="cpf"
                        type="text"
                        value="{{ \App\Support\Cpf::format(old('cpf')) }}"
                        placeholder="000.000.000-00"
                        inputmode="numeric"
                        data-mask="cpf"
                        required
                    >
                </div>
                <button class="btn primary" type="submit">Continuar</button>
            </form>
        </div>
    </section>

    @include('partials.input-masks')
@endsection
