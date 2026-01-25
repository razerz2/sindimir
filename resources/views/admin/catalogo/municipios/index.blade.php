@extends('admin.layouts.app')

@section('title', 'Municípios')

@section('subtitle')
    Gestao de municipios com filtros por estado.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <a class="btn btn-primary" href="{{ route('admin.catalogo.municipios.create') }}">Novo municipio</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
        <x-admin.select
            id="estado_id"
            name="estado_id"
            label="Estado"
            :options="$estados->map(fn ($estado) => ['value' => $estado->id, 'label' => $estado->nome . ' (' . $estado->uf . ')'])->all()"
            :selected="request('estado_id')"
            placeholder="Todos"
        />
        <x-admin.input
            id="nome"
            name="nome"
            label="Nome"
            :value="request('nome')"
        />
        <x-admin.select
            id="status"
            name="status"
            label="Status"
            :options="[
                ['value' => 'ativo', 'label' => 'Ativo'],
                ['value' => 'inativo', 'label' => 'Inativo'],
            ]"
            :selected="request('status')"
            placeholder="Todos"
        />
        <div class="flex items-end gap-2">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.index') }}">Limpar</a>
        </div>
    </form>

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Estado</th>
                    <th>Alunos</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($municipios as $municipio)
                    <tr>
                        <td>{{ $municipio->nome }}</td>
                        <td>{{ $municipio->estado?->nome }} ({{ $municipio->estado?->uf }})</td>
                        <td>{{ $municipio->alunos_count }}</td>
                        <td>
                            <span class="badge {{ $municipio->ativo ? '' : 'warning' }}">
                                {{ $municipio->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.show', $municipio) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12c2.5-5 6.5-8 9.5-8s7 3 9.5 8c-2.5 5-6.5 8-9.5 8s-7-3-9.5-8z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9a3 3 0 100 6 3 3 0 000-6z" />
                                    </svg>
                                    <span>Ver</span>
                                </a>
                                <a class="btn btn-ghost" href="{{ route('admin.catalogo.municipios.edit', $municipio) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h4.5L19.75 9.75l-4.5-4.5L4 16.5V21z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5l4 4" />
                                    </svg>
                                    <span>Editar</span>
                                </a>
                                <form action="{{ route('admin.catalogo.municipios.toggle', $municipio) }}" method="POST" style="display:inline">
                                    @csrf
                                    <button class="btn btn-ghost" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h8M8 12h8M8 18h8" />
                                        </svg>
                                        <span>{{ $municipio->ativo ? 'Inativar' : 'Ativar' }}</span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.catalogo.municipios.destroy', $municipio) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit" @if ($municipio->alunos_count > 0) disabled @endif>
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
                @empty
                    <tr>
                        <td colspan="5">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $municipios->links() }}
@endsection
