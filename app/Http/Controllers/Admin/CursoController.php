<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CursoStoreRequest;
use App\Http\Requests\Admin\CursoUpdateRequest;
use App\Models\Categoria;
use App\Models\Curso;
use App\Services\CursoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CursoController extends Controller
{
    public function __construct(private readonly CursoService $cursoService)
    {
        $this->authorizeResource(Curso::class, 'curso');
    }

    public function index(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPageInput = $request->query('per_page', 10);
        $perPage = is_numeric($perPageInput) ? (int) $perPageInput : 10;
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 10;

        $searchInput = $request->query('search');
        $search = is_string($searchInput) ? trim($searchInput) : '';
        $search = mb_substr($search, 0, 100);

        $sortOptions = ['nome', 'categoria', 'created_at'];
        $sortInput = $request->query('sort', 'created_at');
        $sort = is_string($sortInput) && in_array($sortInput, $sortOptions, true) ? $sortInput : 'created_at';

        $directionInput = $request->query('direction', 'desc');
        $direction = is_string($directionInput) ? strtolower($directionInput) : 'desc';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $cursosQuery = Curso::query()
            ->with('categoria')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($filterQuery) use ($like) {
                    $filterQuery
                        ->where('nome', 'like', $like)
                        ->orWhereHas('categoria', fn ($categoriaQuery) => $categoriaQuery->where('nome', 'like', $like));
                });
            });

        switch ($sort) {
            case 'categoria':
                $cursosQuery->orderBy(
                    Categoria::query()
                        ->select('nome')
                        ->whereColumn('categorias.id', 'cursos.categoria_id')
                        ->limit(1),
                    $direction
                );
                break;
            default:
                $cursosQuery->orderBy($sort, $direction);
                break;
        }

        $cursos = $cursosQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.cursos.index', compact('cursos', 'search', 'perPage', 'perPageOptions', 'sort', 'direction'));
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
