<?php

namespace App\Http\Controllers\Public;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\RacaCor;
use App\Enums\RendaFamiliar;
use App\Enums\Sexo;
use App\Enums\SimNaoNaoDeclarada;
use App\Enums\SituacaoParticipante;
use App\Enums\TipoEntidadeOrigem;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicAlunoStoreRequest;
use App\Models\Deficiencia;
use App\Models\Estado;
use App\Models\NotificationLink;
use App\Services\PublicInscricaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InscricaoController extends Controller
{
    public function __construct(private readonly PublicInscricaoService $inscricaoService)
    {
    }

    public function cpfForm(): View
    {
        return view('public.inscricao-cpf');
    }

    public function cpfSubmit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'string', 'max:14'],
        ]);

        $resultado = $this->inscricaoService->resolverPorCpf($data['cpf']);

        if ($resultado['action'] === 'login') {
            return redirect()->route('login');
        }

        return redirect()
            ->route('public.cadastro')
            ->withInput(['cpf' => $data['cpf']]);
    }

    public function cadastroForm(): View
    {
        $deficiencias = Deficiencia::query()
            ->orderBy('nome')
            ->get(['id', 'nome']);

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

        $estados = Estado::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'uf']);

        return view('public.cadastro-aluno', compact('deficiencias', 'selects', 'estados'));
    }

    public function cadastroStore(PublicAlunoStoreRequest $request): RedirectResponse
    {
        try {
            $this->inscricaoService->cadastrarAluno(
                $request->validated(),
                $request->input('deficiencias', []),
                $request->input('deficiencia_descricao')
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('login');
        }

        return redirect()
            ->route('public.cpf')
            ->with('status', 'Cadastro realizado com sucesso.');
    }

    public function tokenRedirect(string $token): RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de inscrição inválido ou expirado.');
        }

        return redirect()
            ->route('public.cadastro', [
                'token' => $link->token,
                'curso_id' => $link->curso_id,
                'evento_curso_id' => $link->evento_curso_id,
            ])
            ->with('status', 'Link válido! Complete o cadastro para garantir sua vaga.');
    }
}
