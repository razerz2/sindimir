<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioNotificacaoRequest;
use App\Services\RelatorioNotificacaoService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioNotificacaoController extends Controller
{
    public function __construct(private readonly RelatorioNotificacaoService $relatorioNotificacaoService)
    {
    }

    public function index(RelatorioNotificacaoRequest $request): View
    {
        $filtros = $request->validated();
        $notificacoes = $this->relatorioNotificacaoService->listar($filtros);
        $filtrosSelect = $this->relatorioNotificacaoService->getFiltroData();

        return view('admin.relatorios.notificacoes.index', [
            'notificacoes' => $notificacoes,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioNotificacaoRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioNotificacaoService->exportarExcel($filtros);
    }
}
