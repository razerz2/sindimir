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
use App\Models\User;
use App\Services\AlunoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlunoController extends Controller
{
    public function __construct(private readonly AlunoService $alunoService)
    {
        $this->authorizeResource(Aluno::class, 'aluno');
    }

    public function index(): View
    {
        $alunos = Aluno::query()
            ->with(['municipio', 'estadoResidencia'])
            ->latest()
            ->paginate(15);

        return view('admin.alunos.index', compact('alunos'));
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
