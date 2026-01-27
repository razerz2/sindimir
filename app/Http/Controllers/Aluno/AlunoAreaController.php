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
use App\Models\Deficiencia;
use App\Models\Estado;
use App\Services\AlunoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlunoAreaController extends Controller
{
    public function __construct(private readonly AlunoService $alunoService)
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
        return view('aluno.inscricoes');
    }

    public function historico(): View
    {
        return view('aluno.historico');
    }

    public function preferencias(): View
    {
        return view('aluno.preferencias');
    }

    private function getAluno()
    {
        return auth()->user()?->aluno;
    }
}
