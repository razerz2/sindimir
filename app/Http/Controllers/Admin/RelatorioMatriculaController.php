<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioMatriculaRequest;
use App\Services\RelatorioMatriculaService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioMatriculaController extends Controller
{
    public function __construct(private readonly RelatorioMatriculaService $relatorioMatriculaService)
    {
    }

    public function index(RelatorioMatriculaRequest $request): View
    {
        $filtros = $request->validated();
        $matriculas = $this->relatorioMatriculaService->listar($filtros);
        $filtrosSelect = $this->relatorioMatriculaService->getFiltroData();

        return view('admin.relatorios.matriculas.index', [
            'matriculas' => $matriculas,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioMatriculaRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioMatriculaService->exportarExcel($filtros);
    }
}
