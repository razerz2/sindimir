<?php

namespace App\Http\Controllers\Public;

use App\Enums\Escolaridade;
use App\Enums\EstadoCivil;
use App\Enums\NotificationType;
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
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Models\NotificationLink;
use App\Services\MatriculaService;
use App\Services\NotificationLinkService;
use App\Services\NotificationService;
use App\Services\PublicInscricaoService;
use App\Support\Cpf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InscricaoController extends Controller
{
    public function __construct(
        private readonly PublicInscricaoService $inscricaoService,
        private readonly MatriculaService $matriculaService,
        private readonly NotificationLinkService $notificationLinkService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function cpfForm(): View
    {
        return view('public.inscricao-cpf');
    }

    public function cpfSubmit(Request $request): RedirectResponse
    {
        $request->merge([
            'cpf' => Cpf::normalize($request->input('cpf')),
        ]);
        $data = $request->validate([
            'cpf' => [
                'required',
                'string',
                'digits:11',
                function (string $attribute, mixed $value, $fail) {
                    if (! Cpf::isValid($value)) {
                        $fail('CPF inválido.');
                    }
                },
            ],
            'evento_curso_id' => ['nullable', 'integer', 'exists:evento_cursos,id'],
        ]);

        $resultado = $this->inscricaoService->resolverPorCpf($data['cpf']);

        if ($resultado['action'] === 'login') {
            return redirect()
                ->route('aluno.login')
                ->with('status', 'Este CPF já possui acesso. Faça login para continuar.');
        }

        $routeParams = [];

        if (! empty($data['evento_curso_id'])) {
            $routeParams['evento_curso_id'] = $data['evento_curso_id'];
        }

        if ($resultado['aluno']) {
            $aluno = $resultado['aluno'];
            $input = collect($aluno->getFillable())
                ->reject(fn (string $campo) => $campo === 'user_id')
                ->mapWithKeys(function (string $campo) use ($aluno) {
                    $valor = $aluno->getAttribute($campo);

                    if ($valor instanceof \BackedEnum) {
                        $valor = $valor->value;
                    }

                    return [$campo => $valor];
                })
                ->all();

            $input['cpf'] = $aluno->cpf;
            $input['data_nascimento'] = $aluno->data_nascimento?->format('Y-m-d');
            $input['deficiencias'] = $aluno->deficiencias()->pluck('deficiencias.id')->all();
            $input['evento_curso_id'] = $data['evento_curso_id'] ?? null;

            return redirect()
                ->route('public.cadastro', $routeParams)
                ->withInput($input)
                ->with('status', 'CPF já cadastrado. Confirme seus dados para continuar.');
        }

        return redirect()
            ->route('public.cadastro', $routeParams)
            ->withInput($data);
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
            $deficiencias = $request->has('deficiencias')
                ? $request->input('deficiencias', [])
                : null;
            $deficienciaDescricao = $request->has('deficiencias')
                ? $request->input('deficiencia_descricao')
                : null;
            $aluno = $this->inscricaoService->cadastrarAluno(
                $request->validated(),
                $deficiencias,
                $deficienciaDescricao
            );

            $eventoId = $request->integer('evento_curso_id');
            $statusMessage = 'Cadastro realizado com sucesso.';

            if ($eventoId) {
                $resultado = $this->inscricaoService->inscreverAlunoNoEvento($aluno, $eventoId);
                if ($resultado['tipo'] === 'lista_espera') {
                    $statusMessage = 'Inscrição registrada. Você entrou na lista de espera.';
                } else {
                    $evento = \App\Models\EventoCurso::query()
                        ->with('curso')
                        ->find($eventoId);
                    if ($evento && $evento->curso) {
                        $link = $this->notificationLinkService->resolve(
                            $aluno,
                            $evento->curso,
                            $evento,
                            NotificationType::INSCRICAO_CONFIRMAR
                        );

                        $this->notificationService->disparar(
                            [$aluno],
                            $evento,
                            NotificationType::INSCRICAO_CONFIRMAR,
                            false,
                            true
                        );
                    }

                    return redirect()
                        ->route('public.inscricao.realizada')
                        ->with('status', 'Inscrição realizada com sucesso.');
                }
            }
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('aluno.login')
                ->with('status', 'Este CPF já possui acesso. Faça login para continuar.');
        }

        return redirect()
            ->route('public.cpf')
            ->with('status', $statusMessage ?? 'Cadastro realizado com sucesso.');
    }

    public function tokenRedirect(string $token): RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->with('aluno.deficiencias')
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de inscrição inválido ou expirado.');
        }

        if ($link->notification_type === NotificationType::MATRICULA_CONFIRMADA->value) {
            return redirect()
                ->route('public.matricula.visualizar', ['token' => $link->token]);
        }

        if ($link->notification_type === NotificationType::INSCRICAO_CONFIRMAR->value) {
            return redirect()
                ->route('public.inscricao.confirmar', ['token' => $link->token]);
        }

        if ($link->aluno_id && $link->evento_curso_id) {
            $matricula = Matricula::query()
                ->where('aluno_id', $link->aluno_id)
                ->where('evento_curso_id', $link->evento_curso_id)
                ->first();

            if ($matricula?->status === \App\Enums\StatusMatricula::Confirmada) {
                $evento = \App\Models\EventoCurso::query()
                    ->with('curso')
                    ->find($link->evento_curso_id);
                if ($evento && $evento->curso) {
                    $linkMatricula = $this->notificationLinkService->resolve(
                        $link->aluno,
                        $evento->curso,
                        $evento,
                        NotificationType::MATRICULA_CONFIRMADA
                    );

                    return redirect()
                        ->route('public.matricula.visualizar', ['token' => $linkMatricula->token]);
                }
            }

            if ($matricula?->status === \App\Enums\StatusMatricula::Pendente) {
                $evento = \App\Models\EventoCurso::query()
                    ->with('curso')
                    ->find($link->evento_curso_id);
                if ($evento && $evento->curso) {
                    $linkConfirmacao = $this->notificationLinkService->resolve(
                        $link->aluno,
                        $evento->curso,
                        $evento,
                        NotificationType::INSCRICAO_CONFIRMAR
                    );

                    return redirect()
                        ->route('public.inscricao.confirmar', ['token' => $linkConfirmacao->token]);
                }
            }
        }

        if ($link->aluno_id && $link->evento_curso_id) {
            $matricula = Matricula::query()
                ->where('aluno_id', $link->aluno_id)
                ->where('evento_curso_id', $link->evento_curso_id)
                ->first();

            if ($matricula?->status === \App\Enums\StatusMatricula::Confirmada) {
                $evento = \App\Models\EventoCurso::query()
                    ->with('curso')
                    ->find($link->evento_curso_id);
                if ($evento && $evento->curso) {
                    $linkMatricula = $this->notificationLinkService->resolve(
                        $link->aluno,
                        $evento->curso,
                        $evento,
                        NotificationType::MATRICULA_CONFIRMADA
                    );

                    return redirect()
                        ->route('public.matricula.visualizar', ['token' => $linkMatricula->token]);
                }
            }

            if ($matricula?->status === \App\Enums\StatusMatricula::Pendente) {
                $evento = \App\Models\EventoCurso::query()
                    ->with('curso')
                    ->find($link->evento_curso_id);
                if ($evento && $evento->curso) {
                    $linkConfirmacao = $this->notificationLinkService->resolve(
                        $link->aluno,
                        $evento->curso,
                        $evento,
                        NotificationType::INSCRICAO_CONFIRMAR
                    );

                    return redirect()
                        ->route('public.inscricao.confirmar', ['token' => $linkConfirmacao->token]);
                }
            }
        }

        $routeParams = [
            'token' => $link->token,
            'curso_id' => $link->curso_id,
            'evento_curso_id' => $link->evento_curso_id,
        ];

        if ($link->aluno) {
            $aluno = $link->aluno;
            $input = collect($aluno->getFillable())
                ->reject(fn (string $campo) => $campo === 'user_id')
                ->mapWithKeys(function (string $campo) use ($aluno) {
                    $valor = $aluno->getAttribute($campo);

                    if ($valor instanceof \BackedEnum) {
                        $valor = $valor->value;
                    }

                    return [$campo => $valor];
                })
                ->all();

            $input['cpf'] = $aluno->cpf;
            $input['data_nascimento'] = $aluno->data_nascimento?->format('Y-m-d');
            $input['deficiencias'] = $aluno->deficiencias()->pluck('deficiencias.id')->all();
            $input['evento_curso_id'] = $link->evento_curso_id;

            return redirect()
                ->route('public.cadastro', $routeParams)
                ->withInput($input)
                ->with('status', 'Link válido! Confirme seus dados para garantir sua vaga.');
        }

        return redirect()
            ->route('public.cadastro', $routeParams)
            ->with('status', 'Link válido! Complete o cadastro para garantir sua vaga.');
    }

    public function confirmarInscricao(string $token): View|RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de confirmação inválido ou expirado.');
        }

        if (! $link->evento_curso_id) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Confirmação indisponível para este link.');
        }

        $matricula = Matricula::query()
            ->with(['eventoCurso.curso', 'aluno'])
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada para confirmação.');
        }

        if (! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada para confirmação.');
        }

        $evento = $matricula->eventoCurso;
        $curso = $evento->curso;
        $datas = $evento->data_inicio
            ? ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio)
                ? $evento->data_inicio->format('d/m/Y') . ' a ' . $evento->data_fim->format('d/m/Y')
                : $evento->data_inicio->format('d/m/Y'))
            : 'Data ainda não informada.';
        $horario = $evento->horario_inicio ? substr($evento->horario_inicio, 0, 5) : 'Não informado';
        $turno = $evento->turno?->value
            ? ucfirst(str_replace('_', ' ', $evento->turno->value))
            : 'Não informado';
        $cargaHoraria = $evento->carga_horaria ?: 'Não informada';
        $primeiroNome = $matricula->aluno?->nome_completo
            ? explode(' ', trim($matricula->aluno->nome_completo))[0]
            : 'Aluno';

        return view('public.inscricao-confirmar', compact(
            'matricula',
            'evento',
            'curso',
            'datas',
            'horario',
            'turno',
            'cargaHoraria',
            'primeiroNome',
            'token'
        ));
    }

    public function confirmarInscricaoSim(string $token): RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de confirmação inválido ou expirado.');
        }

        if (! $link->evento_curso_id) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Confirmação indisponível para este link.');
        }

        $matricula = Matricula::query()
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada para confirmação.');
        }

        $evento = EventoCurso::query()
            ->with('curso')
            ->find($link->evento_curso_id);

        if (! $evento || ! $evento->curso) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada para confirmação.');
        }

        try {
            $this->matriculaService->confirmarMatricula($matricula);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('public.cursos')
                ->with('status', $exception->getMessage());
        }

        $linkMatricula = $this->notificationLinkService->resolve(
            $matricula->aluno,
            $evento->curso,
            $evento,
            NotificationType::MATRICULA_CONFIRMADA
        );

        return redirect()
            ->route('public.matricula.visualizar', ['token' => $linkMatricula->token]);
    }

    public function cancelarInscricaoNao(string $token): RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de confirmação inválido ou expirado.');
        }

        if (! $link->evento_curso_id) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Cancelamento indisponível para este link.');
        }

        $matricula = Matricula::query()
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada para cancelamento.');
        }

        $this->matriculaService->cancelarMatricula($matricula);
        $matricula->loadMissing('aluno');

        $evento = EventoCurso::query()
            ->with('curso')
            ->find($link->evento_curso_id);
        if ($evento && $evento->curso) {
            if ($matricula->aluno) {
                $this->notificationService->disparar(
                    [$matricula->aluno],
                $evento,
                NotificationType::INSCRICAO_CANCELADA
                );
            }
        }

        return redirect()
            ->route('public.inscricao.cancelada', ['token' => $token]);
    }

    public function cancelarInscricaoPagina(string $token): View|RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Cancelamento indisponível para este link.');
        }

        $matricula = Matricula::query()
            ->with(['eventoCurso.curso', 'aluno'])
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Inscrição não encontrada.');
        }

        $evento = $matricula->eventoCurso;
        $curso = $evento->curso;
        $datas = $evento->data_inicio
            ? ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio)
                ? $evento->data_inicio->format('d/m/Y') . ' a ' . $evento->data_fim->format('d/m/Y')
                : $evento->data_inicio->format('d/m/Y'))
            : 'Data ainda não informada.';

        return view('public.inscricao-cancelada', compact('curso', 'datas'));
    }

    public function inscricaoRealizada(): View
    {
        return view('public.inscricao-realizada');
    }

    public function visualizarMatricula(string $token): View|RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid()) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de matrícula inválido ou expirado.');
        }

        if (! $link->evento_curso_id) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Matrícula não disponível para este link.');
        }

        $matricula = Matricula::query()
            ->with(['eventoCurso.curso', 'aluno'])
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Matrícula não encontrada.');
        }

        $evento = $matricula->eventoCurso;
        $curso = $evento->curso;
        $datas = $evento->data_inicio
            ? ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio)
                ? $evento->data_inicio->format('d/m/Y') . ' a ' . $evento->data_fim->format('d/m/Y')
                : $evento->data_inicio->format('d/m/Y'))
            : 'Data ainda não informada.';
        $horario = $evento->horario_inicio ? substr($evento->horario_inicio, 0, 5) : 'Não informado';
        $turno = $evento->turno?->value
            ? ucfirst(str_replace('_', ' ', $evento->turno->value))
            : 'Não informado';
        $cargaHoraria = $evento->carga_horaria ?: 'Não informada';

        return view('public.matricula', compact(
            'matricula',
            'evento',
            'curso',
            'datas',
            'horario',
            'turno',
            'cargaHoraria'
        ));
    }

    public function cancelarMatriculaPublica(string $token): RedirectResponse
    {
        $link = NotificationLink::query()
            ->where('token', $token)
            ->first();

        if (! $link || ! $link->isValid() || $link->notification_type !== NotificationType::MATRICULA_CONFIRMADA->value) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Link de matrícula inválido ou expirado.');
        }

        if (! $link->evento_curso_id) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Cancelamento indisponível para este link.');
        }

        $matricula = Matricula::query()
            ->where('aluno_id', $link->aluno_id)
            ->where('evento_curso_id', $link->evento_curso_id)
            ->first();

        if (! $matricula) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Matrícula não encontrada.');
        }

        if ($matricula->status === \App\Enums\StatusMatricula::Cancelada) {
            return redirect()
                ->route('public.cursos')
                ->with('status', 'Sua matrícula já foi cancelada.');
        }

        $this->matriculaService->cancelarMatricula($matricula);

        return redirect()
            ->route('public.matricula.visualizar', ['token' => $link->token]);
    }
}
