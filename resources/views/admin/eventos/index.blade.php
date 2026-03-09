@extends('admin.layouts.app')

@section('title', 'Eventos')

@section('subtitle')
    Controle de turmas e eventos associados aos cursos.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Eventos', 'icon' => 'calendar', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.eventos.create') }}">Novo evento</x-admin.action>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Número</th>
                    <th>Período</th>
                    <th>Município</th>
                    <th>Turno</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($eventos as $evento)
                    <tr>
                        <td>
                            @if ($evento->curso)
                                {{ $evento->curso->nome }}@if ($evento->curso->trashed()) (removido) @endif
                            @else
                                -
                            @endif
                        </td>
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
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.eventos.show', $evento) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.eventos.edit', $evento) }}">Editar</x-admin.action>
                                <form action="{{ route('admin.eventos.destroy', $evento) }}" method="POST" style="display:inline"
                                    data-confirm="Deseja realmente excluir este evento? Esta ação é irreversível.">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.action variant="danger" icon="trash" type="submit">Excluir</x-admin.action>
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
