<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioEventoRequest;
use App\Services\RelatorioEventoService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioEventoController extends Controller
{
    public function __construct(private readonly RelatorioEventoService $relatorioEventoService)
    {
    }

    public function index(RelatorioEventoRequest $request): View
    {
        $filtros = $request->validated();
        $eventos = $this->relatorioEventoService->listar($filtros);
        $filtrosSelect = $this->relatorioEventoService->getFiltroData();

        return view('admin.relatorios.eventos.index', [
            'eventos' => $eventos,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioEventoRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioEventoService->exportarExcel($filtros);
    }
}
