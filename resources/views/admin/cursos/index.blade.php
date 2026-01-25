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
                        <td>{{ $curso->categoria?->nome ?? '-' }}</td>
                        <td>{{ $curso->validade?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $curso->limite_vagas }}</td>
                        <td>
                            <span class="badge {{ $curso->ativo ? '' : 'warning' }}">
                                {{ $curso->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost" href="{{ route('admin.cursos.show', $curso) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12c2.5-5 6.5-8 9.5-8s7 3 9.5 8c-2.5 5-6.5 8-9.5 8s-7-3-9.5-8z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9a3 3 0 100 6 3 3 0 000-6z" />
                                    </svg>
                                    <span>Ver</span>
                                </a>
                                <a class="btn btn-ghost" href="{{ route('admin.cursos.edit', $curso) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h4.5L19.75 9.75l-4.5-4.5L4 16.5V21z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5l4 4" />
                                    </svg>
                                    <span>Editar</span>
                                </a>
                                <form action="{{ route('admin.cursos.destroy', $curso) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7l1 12.5A2.5 2.5 0 0 0 8.5 22h7a2.5 2.5 0 0 0 2.5-2.5L19 7" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1V7" />
                                        </svg>
                                        <span>Excluir</span>
                                    </button>
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
