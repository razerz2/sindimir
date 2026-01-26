<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;

class MatriculaController extends Controller
{
    public function __construct(private readonly MatriculaService $matriculaService)
    {
    }

    public function cancelar(Matricula $matricula): RedirectResponse
    {
        $this->authorize('delete', $matricula);

        $this->matriculaService->cancelarMatriculaEEnviarParaListaEspera($matricula);

        return back()->with('status', 'Inscrição removida com sucesso.');
    }
}
