<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TurnoEvento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventoManualInscricaoSearchRequest;
use App\Http\Requests\Admin\EventoManualInscricaoStoreRequest;
use App\Http\Requests\Admin\EventoCursoStoreRequest;
use App\Http\Requests\Admin\EventoCursoUpdateRequest;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Services\AdminEventoInscricaoService;
use App\Services\EventoCursoService;
use App\Support\Cpf;
use App\Support\Phone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class EventoCursoController extends Controller
{
    public function __construct(
        private readonly EventoCursoService $eventoCursoService,
        private readonly AdminEventoInscricaoService $adminEventoInscricaoService
    )
    {
        $this->authorizeResource(EventoCurso::class, 'evento');
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
        $turnoSearch = mb_strtolower(str_replace(' ', '_', $search));

        $sortOptions = ['numero_evento', 'curso', 'municipio', 'turno', 'data_inicio', 'created_at'];
        $sortInput = $request->query('sort', 'created_at');
        $sort = is_string($sortInput) && in_array($sortInput, $sortOptions, true) ? $sortInput : 'created_at';

        $directionInput = $request->query('direction', 'desc');
        $direction = is_string($directionInput) ? strtolower($directionInput) : 'desc';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $eventosQuery = EventoCurso::query()
            ->with(['curso' => fn ($query) => $query->withTrashed()])
            ->when($search !== '', function ($query) use ($search, $turnoSearch) {
                $like = '%' . $search . '%';

                $query->where(function ($filterQuery) use ($like, $turnoSearch) {
                    $filterQuery
                        ->where('numero_evento', 'like', $like)
                        ->orWhere('municipio', 'like', $like)
                        ->orWhereRaw('LOWER(turno) LIKE ?', ['%' . $turnoSearch . '%'])
                        ->orWhereHas('curso', fn ($cursoQuery) => $cursoQuery->withTrashed()->where('nome', 'like', $like));
                });
            });

        switch ($sort) {
            case 'curso':
                $eventosQuery->orderBy(
                    Curso::withTrashed()
                        ->select('nome')
                        ->whereColumn('cursos.id', 'evento_cursos.curso_id')
                        ->limit(1),
                    $direction
                );
                break;
            default:
                $eventosQuery->orderBy($sort, $direction);
                break;
        }

        $eventos = $eventosQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.eventos.index', compact('eventos', 'search', 'perPage', 'perPageOptions', 'sort', 'direction'));
    }

    public function create(): View
    {
        $cursos = Curso::query()->orderBy('nome')->get(['id', 'nome']);
        $turnos = TurnoEvento::cases();

        return view('admin.eventos.create', compact('cursos', 'turnos'));
    }

    public function store(EventoCursoStoreRequest $request): RedirectResponse
    {
        $evento = $this->eventoCursoService->create($request->validated());

        return redirect()
            ->route('admin.eventos.show', $evento)
            ->with('status', 'Evento criado com sucesso.');
    }

    public function show(EventoCurso $evento): View
    {
        $evento->load(['curso' => fn ($query) => $query->withTrashed()]);
        $resumoVagas = $this->eventoCursoService->resumoVagas($evento);
        $inscritos = $this->eventoCursoService->inscritos($evento);
        $listaEspera = $this->eventoCursoService->listaEspera($evento);

        return view('admin.eventos.show', compact('evento', 'resumoVagas', 'inscritos', 'listaEspera'));
    }

    public function inscritos(EventoCurso $evento): View
    {
        $this->authorize('view', $evento);
        $evento->load(['curso' => fn ($query) => $query->withTrashed()]);
        $inscritos = $this->eventoCursoService->inscritos($evento);

        return view('admin.eventos.inscritos', compact('evento', 'inscritos'));
    }

    public function buscarAlunosParaInscricao(
        EventoManualInscricaoSearchRequest $request,
        EventoCurso $evento
    ): JsonResponse
    {
        $alunos = $this->adminEventoInscricaoService->buscarAlunos((string) $request->input('termo'));

        $data = $alunos->map(static fn ($aluno) => [
            'id' => $aluno->id,
            'nome' => (string) $aluno->nome_completo,
            'cpf' => Cpf::format($aluno->cpf) ?: '-',
            'email' => $aluno->email ?: '-',
            'telefone' => Phone::format($aluno->celular ?: $aluno->telefone) ?: '-',
        ])->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function inscreverAluno(
        EventoManualInscricaoStoreRequest $request,
        EventoCurso $evento
    ): RedirectResponse
    {
        try {
            $payload = $this->adminEventoInscricaoService->inscreverAluno(
                $evento,
                (int) $request->input('aluno_id')
            );

            return back()->with('status', $payload['mensagem']);
        } catch (ModelNotFoundException) {
            return back()->with('status', 'Aluno nao encontrado.');
        } catch (RuntimeException $exception) {
            return back()->with('status', $exception->getMessage());
        }
    }

    public function edit(EventoCurso $evento): View
    {
        $cursos = Curso::query()->orderBy('nome')->get(['id', 'nome']);
        $turnos = TurnoEvento::cases();

        return view('admin.eventos.edit', compact('evento', 'cursos', 'turnos'));
    }

    public function update(EventoCursoUpdateRequest $request, EventoCurso $evento): RedirectResponse
    {
        $this->eventoCursoService->update($evento, $request->validated());

        return redirect()
            ->route('admin.eventos.show', $evento)
            ->with('status', 'Evento atualizado com sucesso.');
    }

    public function destroy(EventoCurso $evento): RedirectResponse
    {
        $this->eventoCursoService->delete($evento);

        return redirect()
            ->route('admin.eventos.index')
            ->with('status', 'Evento removido com sucesso.');
    }
}
