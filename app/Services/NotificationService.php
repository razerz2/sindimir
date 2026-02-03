<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\StatusMatricula;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Aluno;
use App\Models\ContatoExterno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\NotificationLink;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Support\WhatsAppMessageFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private const DESTINATARIO_ALUNO = 'aluno';
    private const DESTINATARIO_CONTATO_EXTERNO = 'contato_externo';

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly NotificationLinkService $linkService
    ) {
    }

    public function previewTemplate(Aluno $aluno, Curso $curso, NotificationType $notificationType): array
    {
        $datas = 'Datas definidas pela equipe.';
        $vagasDisponiveis = (int) $curso->limite_vagas;
        $linkUrl = $notificationType === NotificationType::INSCRICAO_CONFIRMAR
            ? route('public.inscricao.confirmar', ['token' => 'preview'])
            : route('public.cursos');
        if (in_array($notificationType, [NotificationType::EVENTO_CANCELADO, NotificationType::INSCRICAO_CANCELADA], true)) {
            $linkUrl = '';
        }
        $destinatarioNome = (string) $aluno->nome_completo;
        $context = $this->buildTemplateContext($destinatarioNome, $curso, null, $linkUrl, $datas, $vagasDisponiveis);

        $emailTemplate = $this->getTemplate($notificationType, 'email');
        $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

        $assunto = $emailTemplate && $emailTemplate->assunto
            ? $this->renderTemplate($emailTemplate->assunto, $context)
            : "Convite para {$curso->nome}";
        $corpoEmail = $this->resolveMensagem(
            $notificationType,
            $emailTemplate?->conteudo,
            $context,
            $destinatarioNome,
            $curso,
            null,
            $linkUrl,
            $datas,
            $vagasDisponiveis
        );
        $textoWhatsApp = $this->resolveMensagem(
            $notificationType,
            $whatsappTemplate?->conteudo,
            $context,
            $destinatarioNome,
            $curso,
            null,
            $linkUrl,
            $datas,
            $vagasDisponiveis
        );
        $corpoEmail = $this->normalizeMensagem($corpoEmail, $destinatarioNome, $notificationType, $linkUrl);
        $textoWhatsApp = $this->normalizeMensagem($textoWhatsApp, $destinatarioNome, $notificationType, $linkUrl);
        $textoWhatsApp = WhatsAppMessageFormatter::format($textoWhatsApp);

        return [
            'assunto_email' => $assunto,
            'corpo_email' => $corpoEmail,
            'texto_whatsapp' => $textoWhatsApp,
        ];
    }

    /**
     * @param iterable<Aluno> $alunos
     */
    public function disparar(
        iterable $alunos,
        Curso|EventoCurso $destino,
        NotificationType $notificationType = NotificationType::CURSO_DISPONIVEL,
        ?bool $forcarEmail = null,
        ?bool $forcarWhatsApp = null,
        ?int $validadeMinutos = null
    ): void
    {
        $destinoEvento = $destino instanceof EventoCurso ? $destino->loadMissing('curso') : null;
        $curso = $destinoEvento ? $destinoEvento->curso : $destino;

        $destinatarios = $this->resolveDestinatarios($alunos);

        if ($destinatarios->isEmpty()) {
            return;
        }

        $vagasDisponiveis = $this->contarVagas($curso, $destinoEvento);
        $datas = $this->formatarDatas($destinoEvento);
        [$emailAtivo, $whatsappAtivo] = $this->resolveCanaisAtivos($forcarEmail, $forcarWhatsApp);

        if (! $emailAtivo && ! $whatsappAtivo) {
            return;
        }

        Log::info('Disparo de notificações iniciado.', [
            'destinatarios' => $destinatarios->count(),
            'destinatarios_config' => $this->resolveDestinatariosConfig(),
            'notification_type' => $notificationType->value,
        ]);

        $emailsEnviados = [];
        $telefonesEnviados = [];

        foreach ($destinatarios as $destinatario) {
            $destinatarioNome = (string) ($destinatario['nome'] ?? '');
            $link = null;
            if ($destinatario['tipo'] === self::DESTINATARIO_ALUNO && $destinatario['aluno'] instanceof Aluno) {
                $link = $this->linkService->resolve(
                    $destinatario['aluno'],
                    $curso,
                    $destinoEvento,
                    $notificationType,
                    $validadeMinutos
                );
            }
            $linkUrl = $this->resolveLinkUrl($link, $notificationType);
            if (in_array($notificationType, [NotificationType::EVENTO_CANCELADO, NotificationType::INSCRICAO_CANCELADA], true)) {
                $linkUrl = '';
            }
            $context = $this->buildTemplateContext(
                $destinatarioNome,
                $curso,
                $destinoEvento,
                $linkUrl,
                $datas,
                $vagasDisponiveis
            );

            $emailTemplate = $this->getTemplate($notificationType, 'email');
            $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

            $assunto = $emailTemplate && $emailTemplate->assunto
                ? $this->renderTemplate($emailTemplate->assunto, $context)
                : "Convite para {$curso->nome}";
            $mensagemEmail = $this->resolveMensagem(
                $notificationType,
                $emailTemplate?->conteudo,
                $context,
                $destinatarioNome,
                $curso,
                $destinoEvento,
                $linkUrl,
                $datas,
                $vagasDisponiveis
            );
            $mensagemWhatsApp = $this->resolveMensagem(
                $notificationType,
                $whatsappTemplate?->conteudo,
                $context,
                $destinatarioNome,
                $curso,
                $destinoEvento,
                $linkUrl,
                $datas,
                $vagasDisponiveis
            );
            $mensagemEmail = $this->normalizeMensagem($mensagemEmail, $destinatarioNome, $notificationType, $linkUrl);
            $mensagemWhatsApp = $this->normalizeMensagem($mensagemWhatsApp, $destinatarioNome, $notificationType, $linkUrl);

            if ($emailAtivo) {
                $email = (string) ($destinatario['email'] ?? '');
                $emailKey = strtolower(trim($email));
                if (! $this->isValidEmail($email)) {
                    $this->logAttempt('email', 'blocked', 'Email inválido ou não cadastrado.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } elseif ($emailKey !== '' && isset($emailsEnviados[$emailKey])) {
                    $this->logAttempt('email', 'blocked', 'Destinatário duplicado.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } elseif ($this->isRateLimited($destinatario, $curso, $destinoEvento, $notificationType, 'email')) {
                    $this->logAttempt('email', 'blocked', 'Limite diário atingido.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } else {
                    SendEmailNotificationJob::dispatch(
                        $destinatario['tipo'],
                        $destinatario['id'],
                        $destinatarioNome,
                        $email,
                        $curso->id,
                        $destinoEvento?->id,
                        $link?->id,
                        $notificationType->value,
                        $assunto,
                        $mensagemEmail
                    );
                    if ($emailKey !== '') {
                        $emailsEnviados[$emailKey] = true;
                    }
                }
            }

            if ($whatsappAtivo) {
                $numeroNormalizado = $this->normalizeWhatsapp($destinatario['telefone'] ?? null);
                if (! $numeroNormalizado) {
                    $this->logAttempt('whatsapp', 'blocked', 'WhatsApp inválido ou não cadastrado.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } elseif (isset($telefonesEnviados[$numeroNormalizado])) {
                    $this->logAttempt('whatsapp', 'blocked', 'Destinatário duplicado.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } elseif ($this->isRateLimited($destinatario, $curso, $destinoEvento, $notificationType, 'whatsapp')) {
                    $this->logAttempt('whatsapp', 'blocked', 'Limite diário atingido.', $destinatario, $curso, $destinoEvento, $link, $notificationType);
                } else {
                    SendWhatsAppNotificationJob::dispatch(
                        $destinatario['tipo'],
                        $destinatario['id'],
                        $destinatarioNome,
                        $numeroNormalizado,
                        $curso->id,
                        $destinoEvento?->id,
                        $link?->id,
                        $notificationType->value,
                        $mensagemWhatsApp
                    );
                    $telefonesEnviados[$numeroNormalizado] = true;
                }
            }
        }
    }

    /**
     * @return Collection<Aluno>
     */
    private function normalizeAlunos(iterable $alunos): Collection
    {
        $colecao = $alunos instanceof Collection ? $alunos : collect($alunos);

        return $colecao
            ->filter(fn ($aluno) => $aluno instanceof Aluno)
            ->unique('id')
            ->values();
    }

    /**
     * @param iterable<Aluno> $alunos
     * @return Collection<int, array{
     *     tipo: string,
     *     id: int|null,
     *     nome: string,
     *     email: string|null,
     *     telefone: string|null,
     *     aluno: Aluno|null,
     *     contato_externo: ContatoExterno|null
     * }>
     */
    private function resolveDestinatarios(iterable $alunos): Collection
    {
        $modo = $this->resolveDestinatariosConfig();
        $destinatarios = [];

        if ($modo !== 'contatos_externos') {
            foreach ($this->normalizeAlunos($alunos) as $aluno) {
                $destinatarios[] = [
                    'tipo' => self::DESTINATARIO_ALUNO,
                    'id' => $aluno->id,
                    'nome' => (string) $aluno->nome_completo,
                    'email' => $aluno->email,
                    'telefone' => $aluno->celular,
                    'aluno' => $aluno,
                    'contato_externo' => null,
                ];
            }
        }

        if ($modo !== 'alunos') {
            ContatoExterno::query()
                ->orderBy('id')
                ->chunkById(500, function ($contatos) use (&$destinatarios) {
                    foreach ($contatos as $contato) {
                        $destinatarios[] = [
                            'tipo' => self::DESTINATARIO_CONTATO_EXTERNO,
                            'id' => $contato->id,
                            'nome' => (string) $contato->nome,
                            'email' => null,
                            'telefone' => $contato->telefone,
                            'aluno' => null,
                            'contato_externo' => $contato,
                        ];
                    }
                });
        }

        return collect($destinatarios)->values();
    }

    private function resolveDestinatariosConfig(): string
    {
        $modo = (string) $this->configuracaoService->get('notificacao.destinatarios', 'alunos');

        return in_array($modo, ['alunos', 'contatos_externos', 'ambos'], true) ? $modo : 'alunos';
    }

    private function contarVagas(Curso $curso, ?EventoCurso $evento): int
    {
        $limite = (int) $curso->limite_vagas;

        if (! $evento) {
            return $limite;
        }

        $confirmadas = $evento->matriculas()
            ->whereIn('status', [StatusMatricula::Confirmada, StatusMatricula::Pendente])
            ->count();

        return max(0, $limite - $confirmadas);
    }

    private function formatarDatas(?EventoCurso $evento): string
    {
        if (! $evento) {
            return 'Datas definidas pela equipe.';
        }

        $inicio = $evento->data_inicio?->format('d/m/Y');
        $fim = $evento->data_fim?->format('d/m/Y');

        if ($inicio && $fim && $fim !== $inicio) {
            return "{$inicio} a {$fim}";
        }

        return $inicio ?? 'Data ainda não informada.';
    }

    private function buildFallbackMessage(
        string $destinatarioNome,
        Curso $curso,
        ?EventoCurso $evento,
        string $linkUrl,
        string $datas,
        int $vagasDisponiveis,
        NotificationType $notificationType
    ): string {
        if ($notificationType === NotificationType::EVENTO_CRIADO) {
            return $this->buildEventoCriadoMessage($destinatarioNome, $curso, $evento, $linkUrl, $datas, $vagasDisponiveis);
        }

        $primeiroNome = $this->getPrimeiroNome($destinatarioNome);
        $detalhesEvento = [];
        if ($evento?->horario_inicio) {
            $detalhesEvento[] = 'Horario: ' . $this->formatHorario($evento->horario_inicio);
        }
        if ($evento?->carga_horaria) {
            $detalhesEvento[] = "Carga horaria: {$evento->carga_horaria}";
        }
        if ($evento?->turno?->value) {
            $turno = ucfirst(str_replace('_', ' ', $evento->turno->value));
            $detalhesEvento[] = "Turno: {$turno}";
        }

        $linhas = match ($notificationType) {
            NotificationType::EVENTO_CANCELADO => [
                "Olá {$primeiroNome},",
                '',
                "O evento do curso {$curso->nome} foi cancelado.",
                "Datas: {$datas}",
                ...$detalhesEvento,
            ],
            NotificationType::INSCRICAO_CONFIRMAR => [
                "Olá {$primeiroNome},",
                '',
                "Sua inscrição no curso {$curso->nome} precisa ser confirmada.",
                "Datas: {$datas}",
                ...$detalhesEvento,
                '',
                "Confirme sua participação: {$linkUrl}",
            ],
            NotificationType::INSCRICAO_CANCELADA => [
                "Olá {$primeiroNome},",
                '',
                "Sua inscrição no curso {$curso->nome} foi cancelada.",
                "Datas: {$datas}",
                ...$detalhesEvento,
            ],
            NotificationType::LISTA_ESPERA_CHAMADA => [
                "Olá {$primeiroNome},",
                '',
                "Uma vaga foi liberada para o curso {$curso->nome}.",
                "Datas: {$datas}",
                ...$detalhesEvento,
                '',
                "Garanta sua vaga: {$linkUrl}",
            ],
            NotificationType::MATRICULA_CONFIRMADA => [
                "Olá {$primeiroNome},",
                '',
                "Sua matrícula no curso {$curso->nome} foi confirmada.",
                "Datas: {$datas}",
                ...$detalhesEvento,
                '',
                "Acompanhe pelo sistema: {$linkUrl}",
            ],
            default => [
                "Olá {$primeiroNome},",
                '',
                "Curso: {$curso->nome}",
                "Datas: {$datas}",
                ...$detalhesEvento,
                "Vagas disponíveis: {$vagasDisponiveis}",
                '',
                "Garanta sua vaga: {$linkUrl}",
            ],
        };

        return implode(PHP_EOL, array_filter($linhas));
    }

    private function buildEventoCriadoMessage(
        string $destinatarioNome,
        Curso $curso,
        ?EventoCurso $evento,
        string $linkUrl,
        string $datas,
        int $vagasDisponiveis
    ): string {
        $primeiroNome = $this->getPrimeiroNome($destinatarioNome);
        $horario = $evento?->horario_inicio ? $this->formatHorario($evento->horario_inicio) : '';
        $cargaHoraria = $evento?->carga_horaria ? (string) $evento->carga_horaria : '';
        $turno = $evento?->turno?->value
            ? ucfirst(str_replace('_', ' ', $evento->turno->value))
            : '';

        $linhas = [
            "Olá, {$primeiroNome}!",
            'O Sindicato Rural de Miranda e Bodoquena informa a abertura de um novo curso. Confira os detalhes abaixo:',
            '',
            "Curso: {$curso->nome}",
            "Datas: {$datas}",
            "Horario: {$horario}",
            "Carga horaria: {$cargaHoraria}",
            "Turno: {$turno}",
            "Vagas disponíveis: {$vagasDisponiveis}",
            "Garanta sua vaga: {$linkUrl}",
        ];

        return implode(PHP_EOL, $linhas);
    }

    private function getPrimeiroNome(?string $nomeCompleto): string
    {
        $nomeCompleto = trim((string) $nomeCompleto);
        if ($nomeCompleto === '') {
            return '';
        }

        $partes = preg_split('/\s+/', $nomeCompleto);

        return $partes[0] ?? $nomeCompleto;
    }

    private function formatHorario(string $horario): string
    {
        $horario = trim($horario);
        if ($horario === '') {
            return '';
        }

        return strlen($horario) >= 5 ? substr($horario, 0, 5) : $horario;
    }

    /**
     * @return array{bool, bool}
     */
    private function resolveCanaisAtivos(?bool $forcarEmail, ?bool $forcarWhatsApp): array
    {
        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.email_ativo', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false);

        if ($forcarEmail !== null) {
            $emailAtivo = $emailAtivo && $forcarEmail;
        }

        if ($forcarWhatsApp !== null) {
            $whatsappAtivo = $whatsappAtivo && $forcarWhatsApp;
        }

        return [$emailAtivo, $whatsappAtivo];
    }

    private function resolveLinkUrl(?NotificationLink $link, NotificationType $notificationType): string
    {
        if (! $link) {
            $route = match ($notificationType) {
                NotificationType::INSCRICAO_CONFIRMAR,
                NotificationType::MATRICULA_CONFIRMADA => 'public.cpf',
                default => 'public.cursos',
            };

            $path = route($route, [], false);

            return $this->buildAbsoluteUrl($path);
        }

        $route = match ($notificationType) {
            NotificationType::INSCRICAO_CONFIRMAR => 'public.inscricao.confirmar',
            NotificationType::MATRICULA_CONFIRMADA => 'public.matricula.visualizar',
            default => 'public.inscricao.token',
        };

        $path = route($route, ['token' => $link->token], false);

        return $this->buildAbsoluteUrl($path);
    }

    /**
     * @return array<string, string>
     */
    private function buildTemplateContext(
        string $destinatarioNome,
        Curso $curso,
        ?EventoCurso $evento,
        string $linkUrl,
        string $datas,
        int $vagasDisponiveis
    ): array {
        $primeiroNome = $this->getPrimeiroNome($destinatarioNome);
        $horario = $evento?->horario_inicio ? $this->formatHorario($evento->horario_inicio) : '';
        $cargaHoraria = $evento?->carga_horaria ? (string) $evento->carga_horaria : '';
        $turno = $evento?->turno?->value
            ? ucfirst(str_replace('_', ' ', $evento->turno->value))
            : '';

        return [
            '{{aluno_nome}}' => $primeiroNome,
            '{{destinatario_nome}}' => $primeiroNome,
            '{{curso_nome}}' => $curso->nome,
            '{{datas}}' => $datas,
            '{{vagas}}' => (string) $vagasDisponiveis,
            '{{link}}' => $linkUrl,
            '{{horario}}' => $horario,
            '{{carga_horaria}}' => $cargaHoraria,
            '{{turno}}' => $turno,
        ];
    }

    private function buildAbsoluteUrl(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $path = '/' . ltrim($path, '/');

        return $baseUrl . $path;
    }

    private function resolveMensagem(
        NotificationType $notificationType,
        ?string $template,
        array $context,
        string $destinatarioNome,
        Curso $curso,
        ?EventoCurso $evento,
        string $linkUrl,
        string $datas,
        int $vagasDisponiveis
    ): string {
        if ($notificationType === NotificationType::EVENTO_CANCELADO || ! $template) {
            return $this->buildFallbackMessage(
                $destinatarioNome,
                $curso,
                $evento,
                $linkUrl,
                $datas,
                $vagasDisponiveis,
                $notificationType
            );
        }

        $mensagem = $this->renderTemplate($template, $context);

        if ($mensagem === '' || str_contains($mensagem, '{{')) {
            return $this->buildFallbackMessage(
                $destinatarioNome,
                $curso,
                $evento,
                $linkUrl,
                $datas,
                $vagasDisponiveis,
                $notificationType
            );
        }

        return $mensagem;
    }
    private function normalizeMensagem(
        string $mensagem,
        string $destinatarioNome,
        NotificationType $notificationType,
        string $linkUrl
    ): string {
        $mensagem = $this->normalizeNomeDestinatario($mensagem, $destinatarioNome);

        if ($linkUrl !== '' && $this->shouldNormalizeLinks($notificationType)) {
            $mensagem = $this->replaceMessageLinks($mensagem, $linkUrl);
        }

        return $mensagem;
    }

    private function normalizeNomeDestinatario(string $mensagem, string $nomeCompleto): string
    {
        $nomeCompleto = trim($nomeCompleto);
        $primeiroNome = $this->getPrimeiroNome($nomeCompleto);

        if ($primeiroNome === '') {
            return $mensagem;
        }

        $mensagem = preg_replace('/^Olá\s+[^,\n]+/iu', "Olá {$primeiroNome}", $mensagem);

        if ($nomeCompleto !== '' && $nomeCompleto !== $primeiroNome) {
            $mensagem = str_replace($nomeCompleto, $primeiroNome, $mensagem);
        }

        return $mensagem;
    }

    private function shouldNormalizeLinks(NotificationType $notificationType): bool
    {
        return in_array($notificationType, [
            NotificationType::CURSO_DISPONIVEL,
            NotificationType::INSCRICAO_CONFIRMAR,
            NotificationType::LISTA_ESPERA_CHAMADA,
            NotificationType::MATRICULA_CONFIRMADA,
        ], true);
    }

    private function replaceMessageLinks(string $mensagem, string $linkUrl): string
    {
        if ($mensagem === '') {
            return $mensagem;
        }

        if (! preg_match('/https?:\/\/\S+/i', $mensagem)) {
            return $mensagem;
        }

        return preg_replace('/https?:\/\/\S+/i', $linkUrl, $mensagem);
    }

    private function getTemplate(NotificationType $type, string $canal): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('notification_type', $type->value)
            ->where('canal', $canal)
            ->where('ativo', true)
            ->first();
    }

    private function renderTemplate(string $template, array $context): string
    {
        return strtr($template, $context);
    }

    private function logAttempt(
        string $canal,
        string $status,
        string $erro,
        array $destinatario,
        Curso $curso,
        ?EventoCurso $evento,
        ?NotificationLink $link,
        NotificationType $notificationType
    ): void {
        NotificationLog::create([
            'aluno_id' => $destinatario['tipo'] === self::DESTINATARIO_ALUNO ? $destinatario['id'] : null,
            'contato_externo_id' => $destinatario['tipo'] === self::DESTINATARIO_CONTATO_EXTERNO
                ? $destinatario['id']
                : null,
            'tipo_destinatario' => $destinatario['tipo'],
            'curso_id' => $curso->id,
            'evento_curso_id' => $evento?->id,
            'notificacao_link_id' => $link?->id,
            'notification_type' => $notificationType->value,
            'canal' => $canal,
            'status' => $status,
            'erro' => $erro,
        ]);
    }

    public function registrarBloqueio(
        Aluno $aluno,
        Curso $curso,
        ?EventoCurso $evento,
        NotificationType $notificationType,
        string $canal,
        string $motivo,
        ?int $validadeMinutos = null
    ): void {
        $link = $this->linkService->resolve($aluno, $curso, $evento, $notificationType, $validadeMinutos);
        $destinatario = [
            'tipo' => self::DESTINATARIO_ALUNO,
            'id' => $aluno->id,
        ];

        $this->logAttempt(
            $canal,
            'blocked',
            $motivo,
            $destinatario,
            $curso,
            $evento,
            $link,
            $notificationType
        );
    }

    private function isValidEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function normalizeWhatsapp(?string $celular): ?string
    {
        if (! $celular) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $celular ?? '');

        if ($digits === '') {
            return null;
        }

        $numeroSemPais = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;

        if (! preg_match('/^\d{10,11}$/', $numeroSemPais)) {
            return null;
        }

        return '55' . $numeroSemPais;
    }

    private function isRateLimited(
        array $destinatario,
        Curso $curso,
        ?EventoCurso $evento,
        NotificationType $notificationType,
        string $canal
    ): bool {
        $rateLimitAtivo = (bool) $this->configuracaoService->get('notificacao.rate_limit.ativo', true);
        if (! $rateLimitAtivo) {
            return false;
        }

        $limiteDiario = (int) $this->configuracaoService->get('notificacao.rate_limit.limite_diario', 2);
        if ($limiteDiario <= 0) {
            return false;
        }

        $query = NotificationLog::query()
            ->where('curso_id', $curso->id)
            ->where('notification_type', $notificationType->value)
            ->where('canal', $canal)
            ->where('status', '!=', 'blocked')
            ->whereDate('created_at', CarbonImmutable::today());

        if ($destinatario['tipo'] === self::DESTINATARIO_ALUNO) {
            $query->where('tipo_destinatario', self::DESTINATARIO_ALUNO)
                ->where('aluno_id', $destinatario['id']);
        } else {
            $query->where('tipo_destinatario', self::DESTINATARIO_CONTATO_EXTERNO)
                ->where('contato_externo_id', $destinatario['id']);
        }

        if ($evento) {
            $query->where('evento_curso_id', $evento->id);
        }

        $count = $query->count();

        return $count >= $limiteDiario;
    }
}
