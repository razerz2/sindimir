<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoriaStoreRequest;
use App\Http\Requests\Admin\CategoriaUpdateRequest;
use App\Models\Categoria;
use App\Services\CategoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function __construct(private readonly CategoriaService $categoriaService)
    {
        $this->authorizeResource(Categoria::class, 'categoria');
    }

    public function index(): View
    {
        $categorias = Categoria::query()
            ->withCount('cursos')
            ->orderBy('nome')
            ->paginate(15);

        return view('admin.catalogo.categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        $categoria = new Categoria([
            'ativo' => true,
        ]);

        return view('admin.catalogo.categorias.create', compact('categoria'));
    }

    public function store(CategoriaStoreRequest $request): RedirectResponse
    {
        $categoria = $this->categoriaService->create($request->validated());

        return redirect()
            ->route('admin.catalogo.categorias.show', $categoria)
            ->with('status', 'Categoria criada com sucesso.');
    }

    public function show(Categoria $categoria): View
    {
        return view('admin.catalogo.categorias.show', compact('categoria'));
    }

    public function edit(Categoria $categoria): View
    {
        return view('admin.catalogo.categorias.edit', compact('categoria'));
    }

    public function update(CategoriaUpdateRequest $request, Categoria $categoria): RedirectResponse
    {
        $this->categoriaService->update($categoria, $request->validated());

        return redirect()
            ->route('admin.catalogo.categorias.show', $categoria)
            ->with('status', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if (! $this->categoriaService->delete($categoria)) {
            return redirect()
                ->route('admin.catalogo.categorias.index')
                ->with('status', 'Não é possível excluir categorias com cursos vinculados.');
        }

        return redirect()
            ->route('admin.catalogo.categorias.index')
            ->with('status', 'Categoria removida com sucesso.');
    }

    public function toggle(Categoria $categoria): RedirectResponse
    {
        $this->authorize('update', $categoria);
        $this->categoriaService->toggle($categoria);

        return redirect()
            ->route('admin.catalogo.categorias.index')
            ->with('status', 'Status atualizado.');
    }
}
