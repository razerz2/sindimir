<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ListaEsperaController extends Controller
{
    public function __construct(private readonly MatriculaService $matriculaService)
    {
    }

    public function subir(ListaEspera $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $this->matriculaService->moverListaEspera($lista, 'up');

        return back()->with('status', 'Posição atualizada com sucesso.');
    }

    public function descer(ListaEspera $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $this->matriculaService->moverListaEspera($lista, 'down');

        return back()->with('status', 'Posição atualizada com sucesso.');
    }

    public function inscrever(ListaEspera $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $inscrito = $this->matriculaService->inscreverDaListaEspera($lista);

        if (! $inscrito) {
            return back()->with('status', 'Este evento não possui vagas disponíveis no momento.');
        }

        return back()->with('status', 'Inscrição confirmada com sucesso.');
    }

    public function remover(ListaEspera $lista): RedirectResponse
    {
        $this->authorize('delete', $lista);

        $this->matriculaService->removerDaListaEspera($lista);

        return back()->with('status', 'Aluno removido da lista de espera.');
    }
}
