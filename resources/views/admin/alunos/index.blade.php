@extends('admin.layouts.app')

@section('title', 'Alunos')

@section('subtitle')
    Relacao de alunos cadastrados e acompanhamento rapido.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-primary" href="{{ route('admin.alunos.create') }}">Novo aluno</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Municipio</th>
                    <th>Telefone</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($alunos as $aluno)
                    <tr>
                        <td>{{ $aluno->nome_completo }}</td>
                        <td>{{ $aluno->cpf }}</td>
                        <td>{{ $aluno->municipio ?? '-' }}</td>
                        <td>{{ $aluno->celular ?? $aluno->telefone ?? '-' }}</td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost" href="{{ route('admin.alunos.show', $aluno) }}">Ver</a>
                                <a class="btn btn-ghost" href="{{ route('admin.alunos.edit', $aluno) }}">Editar</a>
                                <form action="{{ route('admin.alunos.destroy', $aluno) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $alunos->links() }}
@endsection
