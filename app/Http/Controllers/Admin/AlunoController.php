<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlunoStoreRequest;
use App\Http\Requests\Admin\AlunoUpdateRequest;
use App\Models\Aluno;
use App\Models\Deficiencia;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\User;
use App\Services\AlunoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlunoController extends Controller
{
    public function __construct(private readonly AlunoService $alunoService)
    {
        $this->authorizeResource(Aluno::class, 'aluno');
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
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';

        $sortOptions = ['nome_completo', 'cpf', 'email', 'municipio', 'created_at'];
        $sortInput = $request->query('sort', 'created_at');
        $sort = is_string($sortInput) && in_array($sortInput, $sortOptions, true) ? $sortInput : 'created_at';

        $directionInput = $request->query('direction', 'desc');
        $direction = is_string($directionInput) ? strtolower($directionInput) : 'desc';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $alunosQuery = Aluno::query()
            ->with(['municipio', 'estadoResidencia'])
            ->when($search !== '', function ($query) use ($search, $searchDigits) {
                $like = '%' . $search . '%';

                $query->where(function ($filterQuery) use ($like, $searchDigits) {
                    $filterQuery
                        ->where('nome_completo', 'like', $like)
                        ->orWhere('cpf', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('celular', 'like', $like)
                        ->orWhere('telefone', 'like', $like)
                        ->orWhereHas('municipio', fn ($municipioQuery) => $municipioQuery->where('nome', 'like', $like));

                    if ($searchDigits !== '') {
                        $digitsLike = '%' . $searchDigits . '%';

                        $filterQuery
                            ->orWhere('cpf', 'like', $digitsLike)
                            ->orWhere('celular', 'like', $digitsLike)
                            ->orWhere('telefone', 'like', $digitsLike);
                    }
                });
            });

        switch ($sort) {
            case 'municipio':
                $alunosQuery->orderBy(
                    Municipio::query()
                        ->select('nome')
                        ->whereColumn('municipios.id', 'alunos.municipio_id')
                        ->limit(1),
                    $direction
                );
                break;
            default:
                $alunosQuery->orderBy($sort, $direction);
                break;
        }

        $alunos = $alunosQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.alunos.index', compact('alunos', 'search', 'perPage', 'perPageOptions', 'sort', 'direction'));
    }

    public function create(): View
    {
        return $this->formView('admin.alunos.create');
    }

    public function store(AlunoStoreRequest $request): RedirectResponse
    {
        $aluno = $this->alunoService->create(
            $request->validated(),
            $request->input('deficiencias', []),
            $request->input('deficiencia_descricao')
        );

        return redirect()
            ->route('admin.alunos.show', $aluno)
            ->with('status', 'Aluno criado com sucesso.');
    }

    public function show(Aluno $aluno): View
    {
        $aluno->load(['deficiencias', 'user', 'municipio', 'estadoResidencia']);

        return view('admin.alunos.show', compact('aluno'));
    }

    public function edit(Aluno $aluno): View
    {
        return $this->formView('admin.alunos.edit', $aluno);
    }

    public function update(AlunoUpdateRequest $request, Aluno $aluno): RedirectResponse
    {
        $this->alunoService->update(
            $aluno,
            $request->validated(),
            $request->input('deficiencias', []),
            $request->input('deficiencia_descricao')
        );

        return redirect()
            ->route('admin.alunos.show', $aluno)
            ->with('status', 'Aluno atualizado com sucesso.');
    }

    public function destroy(Aluno $aluno): RedirectResponse
    {
        $aluno->delete();

        return redirect()
            ->route('admin.alunos.index')
            ->with('status', 'Aluno removido com sucesso.');
    }

    private function formView(string $view, ?Aluno $aluno = null): View
    {
        $deficiencias = Deficiencia::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);
        $usuarios = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $estadoSelecionadoId = $aluno?->estado_residencia_id;
        $estados = Estado::query()
            ->where('ativo', true)
            ->when($estadoSelecionadoId, fn ($query) => $query->orWhere('id', $estadoSelecionadoId))
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf']);

        $selects = [
            'sexo' => Sexo::cases(),
            'estado_civil' => EstadoCivil::cases(),
            'raca_cor' => RacaCor::cases(),
            'possui_deficiencia' => SimNaoNaoDeclarada::cases(),
            'escolaridade' => Escolaridade::cases(),
            'renda_familiar' => RendaFamiliar::cases(),
            'situacao_participante' => SituacaoParticipante::cases(),
            'tipo_entidade_origem' => TipoEntidadeOrigem::cases(),
        ];

        return view($view, compact('aluno', 'deficiencias', 'usuarios', 'selects', 'estados'));
    }
}
