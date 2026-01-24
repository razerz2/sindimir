@extends('admin.layouts.app')

@section('title', 'Cursos')

@section('subtitle')
    Gestao de cursos cadastrados no sistema.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-primary" href="{{ route('admin.cursos.create') }}">Novo curso</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Validade</th>
                    <th>Vagas</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cursos as $curso)
                    <tr>
                        <td>{{ $curso->nome }}</td>
                        <td>{{ $curso->categoria ?? '-' }}</td>
                        <td>{{ $curso->validade?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $curso->limite_vagas }}</td>
                        <td>
                            <span class="badge {{ $curso->ativo ? '' : 'warning' }}">
                                {{ $curso->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost" href="{{ route('admin.cursos.show', $curso) }}">Ver</a>
                                <a class="btn btn-ghost" href="{{ route('admin.cursos.edit', $curso) }}">Editar</a>
                                <form action="{{ route('admin.cursos.destroy', $curso) }}" method="POST" style="display:inline">
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

    {{ $cursos->links() }}
@endsection
