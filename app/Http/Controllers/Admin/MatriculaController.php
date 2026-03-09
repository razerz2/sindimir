<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusMatricula;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MatriculaRemocaoRequest;
use App\Models\Matricula;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class MatriculaController extends Controller
{
    public function __construct(private readonly MatriculaService $matriculaService)
    {
    }

    public function cancelar(MatriculaRemocaoRequest $request, Matricula $matricula): RedirectResponse
    {
        $this->authorize('delete', $matricula);

        $data = $request->validated();
        $acao = $data['acao'] ?? 'mover_espera';

        $this->matriculaService->removerInscricao($matricula, $acao);

        $message = $acao === 'confirmar'
            ? 'Inscricao removida com sucesso.'
            : 'Inscricao removida e aluno movido para lista de espera.';

        return back()->with('status', $message);
    }

    public function confirmar(Matricula $matricula): RedirectResponse
    {
        $this->authorize('update', $matricula);

        if ($matricula->status !== StatusMatricula::Pendente) {
            return back()->with('status', 'Apenas inscricoes pendentes podem ser confirmadas.');
        }

        try {
            $this->matriculaService->confirmarMatricula($matricula);
        } catch (RuntimeException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        return back()->with('status', 'Inscricao confirmada com sucesso.');
    }
}
