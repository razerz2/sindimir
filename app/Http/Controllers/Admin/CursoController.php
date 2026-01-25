<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CursoStoreRequest;
use App\Http\Requests\Admin\CursoUpdateRequest;
use App\Models\Categoria;
use App\Models\Curso;
use App\Services\CursoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function __construct(private readonly CursoService $cursoService)
    {
        $this->authorizeResource(Curso::class, 'curso');
    }

    public function index(): View
    {
        $cursos = Curso::query()
            ->with('categoria')
            ->latest()
            ->paginate(15);

        return view('admin.cursos.index', compact('cursos'));
    }

    public function create(): View
    {
        $categorias = Categoria::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('admin.cursos.create', compact('categorias'));
    }

    public function store(CursoStoreRequest $request): RedirectResponse
    {
        $curso = $this->cursoService->create($request->validated());

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso criado com sucesso.');
    }

    public function show(Curso $curso): View
    {
        return view('admin.cursos.show', compact('curso'));
    }

    public function edit(Curso $curso): View
    {
        $categorias = Categoria::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('admin.cursos.edit', compact('curso', 'categorias'));
    }

    public function update(CursoUpdateRequest $request, Curso $curso): RedirectResponse
    {
        $this->cursoService->update($curso, $request->validated());

        return redirect()
            ->route('admin.cursos.show', $curso)
            ->with('status', 'Curso atualizado com sucesso.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        $this->cursoService->delete($curso);

        return redirect()
            ->route('admin.cursos.index')
            ->with('status', 'Curso removido com sucesso.');
    }
}
