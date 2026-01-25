@extends('admin.layouts.app')

@section('title', 'Conteúdo institucional')

@section('content')
    <div class="page-actions">
        <div>
            <h3 class="section-title" style="margin: 0;">Sections</h3>
            <p class="page-subtitle">Organize, ative e edite as sections da home institucional.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.site.sections.create') }}">Nova section</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="content-card">
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
                                    <a class="btn btn-ghost" href="{{ route('admin.site.sections.edit', $section) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h4.5L19.75 9.75l-4.5-4.5L4 16.5V21z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5l4 4" />
                                        </svg>
                                        <span>Editar</span>
                                    </a>
                                    <form action="{{ route('admin.site.sections.toggle', $section) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                            </svg>
                                            <span>{{ $section->ativo ? 'Desativar' : 'Ativar' }}</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.site.sections.duplicate', $section) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-ghost" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.6">
                                                <rect x="8" y="8" width="12" height="12" rx="2" ry="2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 5H6a2 2 0 0 0-2 2v10" />
                                            </svg>
                                            <span>Duplicar</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.site.sections.destroy', $section) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.6">
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
