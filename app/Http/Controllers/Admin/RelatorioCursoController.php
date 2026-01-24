<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RelatorioCursoRequest;
use App\Services\RelatorioCursoService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RelatorioCursoController extends Controller
{
    public function __construct(private readonly RelatorioCursoService $relatorioCursoService)
    {
    }

    public function index(RelatorioCursoRequest $request): View
    {
        $filtros = $request->validated();
        $cursos = $this->relatorioCursoService->listar($filtros);
        $filtrosSelect = $this->relatorioCursoService->getFiltroData();

        return view('admin.relatorios.cursos.index', [
            'cursos' => $cursos,
            'filtros' => $filtros,
            'filtrosSelect' => $filtrosSelect,
        ]);
    }

    public function export(RelatorioCursoRequest $request): Response
    {
        $filtros = $request->validated();

        return $this->relatorioCursoService->exportarExcel($filtros);
    }
}
