@extends('admin.layouts.app')

@section('title', 'Usuários')

@section('subtitle')
    Gestao de acessos ao sistema.
@endsection

@section('content')
    <div class="page-actions">
        <div></div>
        <button class="btn btn-primary" type="button" disabled>Novo usuario</button>
    </div>

    <div class="alert">
        Cadastro de usuarios ainda nao configurado nesta versao.
    </div>

    <div class="table-wrapper">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
@endsection
