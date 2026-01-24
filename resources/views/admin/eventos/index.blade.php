@extends('admin.layouts.app')

@section('title', 'Eventos')

@section('subtitle')
    Controle de turmas e eventos associados aos cursos.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-primary" href="{{ route('admin.eventos.create') }}">Novo evento</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Numero</th>
                    <th>Periodo</th>
                    <th>Municipio</th>
                    <th>Turno</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($eventos as $evento)
                    <tr>
                        <td>{{ $evento->curso?->nome ?? '-' }}</td>
                        <td>{{ $evento->numero_evento }}</td>
                        <td>{{ $evento->data_inicio->format('d/m/Y') }} a {{ $evento->data_fim->format('d/m/Y') }}</td>
                        <td>{{ $evento->municipio }}</td>
                        <td>{{ $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : '-' }}</td>
                        <td>
                            <span class="badge {{ $evento->ativo ? '' : 'warning' }}">
                                {{ $evento->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost" href="{{ route('admin.eventos.show', $evento) }}">Ver</a>
                                <a class="btn btn-ghost" href="{{ route('admin.eventos.edit', $evento) }}">Editar</a>
                                <form action="{{ route('admin.eventos.destroy', $evento) }}" method="POST" style="display:inline">
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

    {{ $eventos->links() }}
@endsection
