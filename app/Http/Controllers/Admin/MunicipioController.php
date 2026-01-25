<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MunicipioStoreRequest;
use App\Http\Requests\Admin\MunicipioUpdateRequest;
use App\Models\Estado;
use App\Models\Municipio;
use App\Services\MunicipioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MunicipioController extends Controller
{
    public function __construct(private readonly MunicipioService $municipioService)
    {
        $this->authorizeResource(Municipio::class, 'municipio');
    }

    public function index(Request $request): View
    {
        $estados = Estado::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf']);

        $municipios = Municipio::query()
            ->with('estado')
            ->withCount('alunos')
            ->when($request->filled('estado_id'), fn ($query) => $query->where('estado_id', $request->input('estado_id')))
            ->when($request->filled('nome'), fn ($query) => $query->where('nome', 'like', '%' . $request->input('nome') . '%'))
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');
                if ($status === 'ativo') {
                    $query->where('ativo', true);
                }
                if ($status === 'inativo') {
                    $query->where('ativo', false);
                }
            })
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('admin.catalogo.municipios.index', compact('municipios', 'estados'));
    }

    public function create(): View
    {
        $municipio = new Municipio(['ativo' => true]);
        $estados = Estado::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf']);

        return view('admin.catalogo.municipios.create', compact('municipio', 'estados'));
    }

    public function store(MunicipioStoreRequest $request): RedirectResponse
    {
        $municipio = $this->municipioService->create($request->validated());

        return redirect()
            ->route('admin.catalogo.municipios.show', $municipio)
            ->with('status', 'Município criado com sucesso.');
    }

    public function show(Municipio $municipio): View
    {
        $municipio->load('estado');

        return view('admin.catalogo.municipios.show', compact('municipio'));
    }

    public function edit(Municipio $municipio): View
    {
        $estados = Estado::query()
            ->where('ativo', true)
            ->when($municipio->estado_id, fn ($query) => $query->orWhereKey($municipio->estado_id))
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf']);

        return view('admin.catalogo.municipios.edit', compact('municipio', 'estados'));
    }

    public function update(MunicipioUpdateRequest $request, Municipio $municipio): RedirectResponse
    {
        $this->municipioService->update($municipio, $request->validated());

        return redirect()
            ->route('admin.catalogo.municipios.show', $municipio)
            ->with('status', 'Município atualizado com sucesso.');
    }

    public function destroy(Municipio $municipio): RedirectResponse
    {
        if (! $this->municipioService->delete($municipio)) {
            return redirect()
                ->route('admin.catalogo.municipios.index')
                ->with('status', 'Não é possível excluir municípios com alunos vinculados.');
        }

        return redirect()
            ->route('admin.catalogo.municipios.index')
            ->with('status', 'Município removido com sucesso.');
    }

    public function toggle(Municipio $municipio): RedirectResponse
    {
        $this->authorize('update', $municipio);
        $this->municipioService->toggle($municipio);

        return redirect()
            ->route('admin.catalogo.municipios.index')
            ->with('status', 'Status atualizado.');
    }

    public function byEstado(Estado $estado): JsonResponse
    {
        $municipios = Municipio::query()
            ->where('estado_id', $estado->id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return response()->json($municipios);
    }
}
