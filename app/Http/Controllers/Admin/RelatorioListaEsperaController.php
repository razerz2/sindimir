<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioListaEsperaRequest;
use App\Services\RelatorioListaEsperaService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioListaEsperaController extends Controller
{
    public function __construct(private readonly RelatorioListaEsperaService $relatorioListaEsperaService)
    {
    }

    public function index(RelatorioListaEsperaRequest $request): View
    {
        $filtros = $request->validated();
        $listaEspera = $this->relatorioListaEsperaService->listar($filtros);
        $filtrosSelect = $this->relatorioListaEsperaService->getFiltroData();

        return view('admin.relatorios.lista-espera.index', [
            'listaEspera' => $listaEspera,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioListaEsperaRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioListaEsperaService->exportarExcel($filtros);
    }
}
