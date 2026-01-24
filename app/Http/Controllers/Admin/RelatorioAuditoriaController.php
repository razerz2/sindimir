<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioAuditoriaRequest;
use App\Services\RelatorioAuditoriaService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioAuditoriaController extends Controller
{
    public function __construct(private readonly RelatorioAuditoriaService $relatorioAuditoriaService)
    {
    }

    public function index(RelatorioAuditoriaRequest $request): View
    {
        $filtros = $request->validated();
        $auditorias = $this->relatorioAuditoriaService->listar($filtros);
        $filtrosSelect = $this->relatorioAuditoriaService->getFiltroData();

        return view('admin.relatorios.auditoria.index', [
            'auditorias' => $auditorias,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioAuditoriaRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioAuditoriaService->exportarExcel($filtros);
    }
}
