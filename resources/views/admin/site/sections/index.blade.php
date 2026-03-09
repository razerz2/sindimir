@extends('admin.layouts.app')

@section('title', 'Conteúdo institucional')

@section('subtitle')
    Organize, ative e edite as seções da página institucional.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Conteúdo institucional', 'icon' => 'book', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.site.sections.create') }}">Nova seção</x-admin.action>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Slug</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="sections-sortable">
                @foreach ($sections as $section)
                    <tr data-id="{{ $section->id }}">
                        <td style="width: 72px;">{{ $section->ordem }}</td>
                        <td>{{ $section->slug }}</td>
                        <td>{{ ucfirst($section->tipo) }}</td>
                        <td>
                            <span class="badge {{ $section->ativo ? 'success' : 'warning' }}">
                                {{ $section->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.site.sections.edit', $section) }}">Editar</x-admin.action>
                                <form action="{{ route('admin.site.sections.toggle', $section) }}" method="POST" style="display:inline">
                                    @csrf
                                    <x-admin.action variant="ghost" icon="settings" type="submit">
                                        {{ $section->ativo ? 'Desativar' : 'Ativar' }}
                                    </x-admin.action>
                                </form>
                                <form action="{{ route('admin.site.sections.duplicate', $section) }}" method="POST" style="display:inline">
                                    @csrf
                                    <x-admin.action variant="ghost" type="submit">Duplicar</x-admin.action>
                                </form>
                                <form action="{{ route('admin.site.sections.destroy', $section) }}" method="POST" style="display:inline">
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

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        const sortableElement = document.getElementById('sections-sortable');
        const reorderUrl = @json(route('admin.site.sections.reorder'));
        const csrfToken = @json(csrf_token());

        Sortable.create(sortableElement, {
            animation: 150,
            onEnd: async () => {
                const ids = Array.from(sortableElement.querySelectorAll('tr'))
                    .map((row) => row.dataset.id);

                await fetch(reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ ids }),
                });
                window.location.reload();
            },
        });
    </script>
@endsection
