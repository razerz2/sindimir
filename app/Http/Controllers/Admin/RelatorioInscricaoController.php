<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioInscricaoRequest;
use App\Services\RelatorioInscricaoService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioInscricaoController extends Controller
{
    public function __construct(private readonly RelatorioInscricaoService $relatorioInscricaoService)
    {
    }

    public function index(RelatorioInscricaoRequest $request): View
    {
        $filtros = $request->validated();
        $inscricoes = $this->relatorioInscricaoService->listar($filtros);
        $filtrosSelect = $this->relatorioInscricaoService->getFiltroData();

        return view('admin.relatorios.inscricoes.index', [
            'inscricoes' => $inscricoes,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioInscricaoRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioInscricaoService->exportarExcel($filtros);
    }
}
