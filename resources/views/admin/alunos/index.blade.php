@extends('admin.layouts.app')

@section('title', 'Alunos')

@section('subtitle')
    Relação de alunos cadastrados e acompanhamento rápido.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Alunos', 'icon' => 'user', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <x-admin.action as="a" variant="primary" icon="plus" href="{{ route('admin.alunos.create') }}">Novo aluno</x-admin.action>
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
                    <th>Município</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($alunos as $aluno)
                    <tr>
                        <td>{{ $aluno->nome_completo }}</td>
                        <td>{{ \App\Support\Cpf::format($aluno->cpf) ?: '-' }}</td>
                        <td>{{ $aluno->municipio?->nome ?? '-' }}</td>
                        <td>{{ \App\Support\Phone::format($aluno->celular ?? $aluno->telefone) ?: '-' }}</td>
                        <td>
                            <div class="table-actions">
                                <x-admin.action as="a" variant="ghost" icon="eye" href="{{ route('admin.alunos.show', $aluno) }}">Ver</x-admin.action>
                                <x-admin.action as="a" variant="ghost" icon="edit" href="{{ route('admin.alunos.edit', $aluno) }}">Editar</x-admin.action>
                                <form action="{{ route('admin.alunos.destroy', $aluno) }}" method="POST" style="display:inline">
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

    {{ $alunos->links() }}
@endsection
