@extends('admin.layouts.app')

@section('title', 'Auditoria')

@section('content')
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Entidade</th>
                <th>ID</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>
                    <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $registro->user?->name ?? 'Sistema' }}</td>
                    <td>{{ ucfirst($registro->acao) }}</td>
                    <td>{{ class_basename($registro->entidade_type) }}</td>
                    <td>{{ $registro->entidade_id ?? '-' }}</td>
                    <td>{{ $registro->ip ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhum registro encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $registros->links() }}
@endsection
