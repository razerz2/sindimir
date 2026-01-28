<?php

namespace App\Http\Controllers\Aluno;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Aluno\AlunoProfileUpdateRequest;
use App\Models\Categoria;
use App\Models\Deficiencia;
use App\Models\Estado;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Enums\StatusMatricula;
use App\Services\AlunoService;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlunoAreaController extends Controller
{
    public function __construct(
        private readonly AlunoService $alunoService,
        private readonly MatriculaService $matriculaService
    )
    {
    }

    public function dashboard(): View
    {
        return view('aluno.dashboard');
    }

    public function perfil(): View
    {
        $aluno = $this->getAluno();

        $deficiencias = Deficiencia::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);
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

        return view('aluno.perfil', compact('aluno', 'deficiencias', 'selects', 'estados'));
    }

    public function perfilUpdate(AlunoProfileUpdateRequest $request): RedirectResponse
    {
        $aluno = $this->getAluno();

        if (! $aluno) {
            return redirect()
                ->route('aluno.perfil')
                ->with('status', 'Perfil do aluno não encontrado.');
        }

        $this->alunoService->update(
            $aluno,
            $request->validated(),
            $request->input('deficiencias', []),
            $request->input('deficiencia_descricao')
        );

        return redirect()
            ->route('aluno.perfil')
            ->with('status', 'Dados atualizados com sucesso.');
    }

    public function inscricoes(): View
    {
        $aluno = $this->getAluno();
        $preferenciasIds = $aluno?->categoriasPreferidas()->pluck('categorias.id')->all() ?? [];

        $eventosQuery = EventoCurso::query()
            ->with(['curso.categoria'])
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->orderBy('data_inicio');

        $eventos = $eventosQuery->paginate(12);
        $eventosPreferidos = collect();

        if (! empty($preferenciasIds)) {
            $eventosPreferidos = (clone $eventosQuery)
                ->whereHas('curso', fn ($query) => $query->whereIn('categoria_id', $preferenciasIds))
                ->limit(4)
                ->get();
        }

        $matriculas = Matricula::query()
            ->where('aluno_id', $aluno?->id)
            ->get()
            ->keyBy('evento_curso_id');

        $listaEspera = ListaEspera::query()
            ->where('aluno_id', $aluno?->id)
            ->get()
            ->keyBy('evento_curso_id');

        return view('aluno.inscricoes', compact(
            'eventos',
            'eventosPreferidos',
            'matriculas',
            'listaEspera',
            'preferenciasIds'
        ));
    }

    public function historico(): View
    {
        $aluno = $this->getAluno();

        $matriculas = Matricula::query()
            ->with('eventoCurso.curso')
            ->where('aluno_id', $aluno?->id)
            ->latest()
            ->get();

        $listaEspera = ListaEspera::query()
            ->with('eventoCurso.curso')
            ->where('aluno_id', $aluno?->id)
            ->latest()
            ->get();

        return view('aluno.historico', compact('matriculas', 'listaEspera'));
    }

    public function preferencias(): View
    {
        $aluno = $this->getAluno();

        $categorias = Categoria::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $preferenciasIds = $aluno?->categoriasPreferidas()->pluck('categorias.id')->all() ?? [];

        return view('aluno.preferencias', compact('categorias', 'preferenciasIds'));
    }

    public function inscrever(EventoCurso $eventoCurso): RedirectResponse
    {
        $aluno = $this->getAluno();

        if (! $aluno) {
            return redirect()
                ->route('aluno.inscricoes')
                ->with('status', 'Aluno não encontrado.');
        }

        $eventoCurso->loadMissing('curso');

        if (! $eventoCurso->ativo || ! $eventoCurso->curso?->ativo) {
            return redirect()
                ->route('aluno.inscricoes')
                ->with('status', 'Inscrições indisponíveis para este curso.');
        }

        if ($this->alunoJaPossuiInscricaoNaData($aluno->id, $eventoCurso)) {
            return redirect()
                ->route('aluno.inscricoes')
                ->with('status', 'Você já possui uma inscrição em um curso agendado para esta data.');
        }

        try {
            $resultado = $this->matriculaService->solicitarInscricao($aluno->id, $eventoCurso->id);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('aluno.inscricoes')
                ->with('status', $exception->getMessage());
        }

        $mensagem = $resultado['tipo'] === 'lista_espera'
            ? 'Inscrição registrada. Você entrou na lista de espera.'
            : 'Inscrição registrada com sucesso. Fique atento aos próximos passos.';

        return redirect()
            ->route('aluno.inscricoes')
            ->with('status', $mensagem);
    }

    public function preferenciasUpdate(Request $request): RedirectResponse
    {
        $aluno = $this->getAluno();

        if (! $aluno) {
            return redirect()
                ->route('aluno.preferencias')
                ->with('status', 'Aluno não encontrado.');
        }

        $data = $request->validate([
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
        ]);

        $aluno->categoriasPreferidas()->sync($data['categorias'] ?? []);

        return redirect()
            ->route('aluno.preferencias')
            ->with('status', 'Preferências atualizadas com sucesso.');
    }

    private function getAluno()
    {
        return auth()->user()?->aluno;
    }

    private function alunoJaPossuiInscricaoNaData(int $alunoId, EventoCurso $eventoCurso): bool
    {
        if (! $eventoCurso->data_inicio) {
            return false;
        }

        $dataEvento = $eventoCurso->data_inicio->toDateString();

        return Matricula::query()
            ->where('aluno_id', $alunoId)
            ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
            ->whereHas('eventoCurso', function ($query) use ($dataEvento, $eventoCurso) {
                $query
                    ->where('ativo', true)
                    ->whereDate('data_inicio', $dataEvento)
                    ->where('id', '!=', $eventoCurso->id)
                    ->whereHas('curso', fn ($cursoQuery) => $cursoQuery->where('ativo', true));
            })
            ->exists();
    }
}
