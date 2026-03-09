@extends('admin.layouts.app')

@section('title', 'Relatórios')

@section('subtitle')
    Central de análises e exportações do sistema.
@endsection

@section('breadcrumb')
    <x-admin.breadcrumb :items="[
        ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'icon' => 'home'],
        ['label' => 'Relatórios', 'icon' => 'settings', 'current' => true],
    ]" />
@endsection

@section('content')
    <div class="space-y-6">
        <p class="text-sm text-[var(--content-text)] opacity-70">
            Selecione um relatório para análise e exportação.
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.matriculas.index') }}">
                <span class="text-base font-semibold">Relatório de matrículas</span>
                <span class="text-sm opacity-70">Status, origem e datas das matrículas.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.notificacoes.index') }}">
                <span class="text-base font-semibold">Relatório de notificações</span>
                <span class="text-sm opacity-70">Auditoria de envios por canal e status.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.inscricoes.index') }}">
                <span class="text-base font-semibold">Relatório de inscrições</span>
                <span class="text-sm opacity-70">Conversão em matrícula e origem.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.lista-espera.index') }}">
                <span class="text-base font-semibold">Relatório de lista de espera</span>
                <span class="text-sm opacity-70">Fila, status e conversões.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.eventos.index') }}">
                <span class="text-base font-semibold">Relatório de eventos</span>
                <span class="text-sm opacity-70">Capacidade, ocupação e status.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.cursos.index') }}">
                <span class="text-base font-semibold">Relatório de cursos</span>
                <span class="text-sm opacity-70">Visão consolidada por curso.</span>
            </a>
            <a class="content-card flex flex-col gap-2 hover:shadow-md" href="{{ route('admin.relatorios.auditoria.index') }}">
                <span class="text-base font-semibold">Relatório de auditoria</span>
                <span class="text-sm opacity-70">Rastreabilidade de ações.</span>
            </a>
        </div>
    </div>
@endsection
