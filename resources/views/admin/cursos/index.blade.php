@extends('admin.layouts.app')

@section('title', 'Cursos')

@section('subtitle')
    Gestão de cursos cadastrados no sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Cursos', 'icon' => 'book', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.cursos.create') }}">Novo curso</x-admin.action>
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
                    <th>Ações</th>
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
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.cursos.show', $curso) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.cursos.edit', $curso) }}">Editar</x-admin.action>
                                <form
                                    action="{{ route('admin.cursos.destroy', $curso) }}"
                                    method="POST"
                                    style="display:inline"
                                    data-confirm="Deseja realmente excluir este curso? Os eventos vinculados também serão removidos."
                                >
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

    {{ $cursos->links() }}
@endsection
