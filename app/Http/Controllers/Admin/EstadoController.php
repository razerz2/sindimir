<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EstadoStoreRequest;
use App\Http\Requests\Admin\EstadoUpdateRequest;
use App\Models\Estado;
use App\Services\EstadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EstadoController extends Controller
{
    public function __construct(private readonly EstadoService $estadoService)
    {
        $this->authorizeResource(Estado::class, 'estado');
    }

    public function index(): View
    {
        $estados = Estado::query()
            ->withCount('municipios')
            ->orderBy('nome')
            ->paginate(15);

        return view('admin.catalogo.estados.index', compact('estados'));
    }

    public function create(): View
    {
        $estado = new Estado([
            'ativo' => true,
        ]);

        return view('admin.catalogo.estados.create', compact('estado'));
    }

    public function store(EstadoStoreRequest $request): RedirectResponse
    {
        $estado = $this->estadoService->create($request->validated());

        return redirect()
            ->route('admin.catalogo.estados.show', $estado)
            ->with('status', 'Estado criado com sucesso.');
    }

    public function show(Estado $estado): View
    {
        $estado->loadCount('municipios');

        return view('admin.catalogo.estados.show', compact('estado'));
    }

    public function edit(Estado $estado): View
    {
        return view('admin.catalogo.estados.edit', compact('estado'));
    }

    public function update(EstadoUpdateRequest $request, Estado $estado): RedirectResponse
    {
        $this->estadoService->update($estado, $request->validated());

        return redirect()
            ->route('admin.catalogo.estados.show', $estado)
            ->with('status', 'Estado atualizado com sucesso.');
    }

    public function destroy(Estado $estado): RedirectResponse
    {
        if (! $this->estadoService->delete($estado)) {
            return redirect()
                ->route('admin.catalogo.estados.index')
                ->with('status', 'Não é possível excluir estados com municípios vinculados.');
        }

        return redirect()
            ->route('admin.catalogo.estados.index')
            ->with('status', 'Estado removido com sucesso.');
    }

    public function toggle(Estado $estado): RedirectResponse
    {
        $this->authorize('update', $estado);
        $this->estadoService->toggle($estado);

        return redirect()
            ->route('admin.catalogo.estados.index')
            ->with('status', 'Status atualizado.');
    }
}
