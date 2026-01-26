<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\StatusMatricula;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\NotificationLink;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class NotificationService
{
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
        $context = $this->buildTemplateContext($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

        $emailTemplate = $this->getTemplate($notificationType, 'email');
        $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

        $assunto = $emailTemplate && $emailTemplate->assunto
            ? $this->renderTemplate($emailTemplate->assunto, $context)
            : "Convite para {$curso->nome}";
        $corpoEmail = $emailTemplate
            ? $this->renderTemplate($emailTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis, $notificationType);
        $textoWhatsApp = $whatsappTemplate
            ? $this->renderTemplate($whatsappTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis, $notificationType);

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

        $colecaoAlunos = $this->normalizeAlunos($alunos);

        if ($colecaoAlunos->isEmpty()) {
            return;
        }

        $vagasDisponiveis = $this->contarVagas($curso, $destinoEvento);
        $datas = $this->formatarDatas($destinoEvento);
        [$emailAtivo, $whatsappAtivo] = $this->resolveCanaisAtivos($forcarEmail, $forcarWhatsApp);

        if (! $emailAtivo && ! $whatsappAtivo) {
            return;
        }

        foreach ($colecaoAlunos as $aluno) {
            $link = $this->linkService->resolve($aluno, $curso, $destinoEvento, $notificationType, $validadeMinutos);
            $linkUrl = $this->resolveLinkUrl($link, $notificationType);
            $context = $this->buildTemplateContext($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

            $emailTemplate = $this->getTemplate($notificationType, 'email');
            $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

            $assunto = $emailTemplate && $emailTemplate->assunto
                ? $this->renderTemplate($emailTemplate->assunto, $context)
                : "Convite para {$curso->nome}";
        $mensagemEmail = $emailTemplate
            ? $this->renderTemplate($emailTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis, $notificationType);
        $mensagemWhatsApp = $whatsappTemplate
            ? $this->renderTemplate($whatsappTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis, $notificationType);

            if ($emailAtivo) {
                if (! $this->isValidEmail($aluno->email)) {
                    $this->logAttempt('email', 'blocked', 'Email inválido ou não cadastrado.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                } elseif ($this->isRateLimited($aluno, $curso, $destinoEvento, $notificationType, 'email')) {
                    $this->logAttempt('email', 'blocked', 'Limite diário atingido.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                } else {
                    SendEmailNotificationJob::dispatch(
                        $aluno->id,
                        $aluno->email,
                        $aluno->nome_completo,
                        $curso->id,
                        $destinoEvento?->id,
                        $link->id,
                        $notificationType->value,
                        $assunto,
                        $mensagemEmail
                    );
                }
            }

            if ($whatsappAtivo) {
                $numeroNormalizado = $this->normalizeWhatsapp($aluno->celular);
                if (! $numeroNormalizado) {
                    $this->logAttempt('whatsapp', 'blocked', 'WhatsApp inválido ou não cadastrado.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                } elseif ($this->isRateLimited($aluno, $curso, $destinoEvento, $notificationType, 'whatsapp')) {
                    $this->logAttempt('whatsapp', 'blocked', 'Limite diário atingido.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                } else {
                    SendWhatsAppNotificationJob::dispatch(
                        $aluno->id,
                        $numeroNormalizado,
                        $curso->id,
                        $destinoEvento?->id,
                        $link->id,
                        $notificationType->value,
                        $mensagemWhatsApp
                    );
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
        Aluno $aluno,
        Curso $curso,
        string $linkUrl,
        string $datas,
        int $vagasDisponiveis,
        NotificationType $notificationType
    ): string {
        $linhas = match ($notificationType) {
            NotificationType::EVENTO_CANCELADO => [
                "Olá {$aluno->nome_completo},",
                '',
                "O evento do curso {$curso->nome} foi cancelado.",
                "Datas: {$datas}",
                '',
                "Mais informações: {$linkUrl}",
            ],
            NotificationType::INSCRICAO_CONFIRMAR => [
                "Olá {$aluno->nome_completo},",
                '',
                "Sua inscrição no curso {$curso->nome} precisa ser confirmada.",
                "Datas: {$datas}",
                '',
                "Confirme sua participação: {$linkUrl}",
            ],
            NotificationType::LISTA_ESPERA_CHAMADA => [
                "Olá {$aluno->nome_completo},",
                '',
                "Uma vaga foi liberada para o curso {$curso->nome}.",
                "Datas: {$datas}",
                '',
                "Garanta sua vaga: {$linkUrl}",
            ],
            NotificationType::MATRICULA_CONFIRMADA => [
                "Olá {$aluno->nome_completo},",
                '',
                "Sua matrícula no curso {$curso->nome} foi confirmada.",
                "Datas: {$datas}",
                '',
                "Acompanhe pelo sistema: {$linkUrl}",
            ],
            default => [
                "Olá {$aluno->nome_completo},",
                '',
                "Curso: {$curso->nome}",
                "Datas: {$datas}",
                "Vagas disponíveis: {$vagasDisponiveis}",
                '',
                "Garanta sua vaga: {$linkUrl}",
            ],
        };

        return implode(PHP_EOL, array_filter($linhas));
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

    private function resolveLinkUrl(NotificationLink $link, NotificationType $notificationType): string
    {
        $route = $notificationType === NotificationType::INSCRICAO_CONFIRMAR
            ? 'public.inscricao.confirmar'
            : 'public.inscricao.token';

        return route($route, ['token' => $link->token]);
    }

    /**
     * @return array<string, string>
     */
    private function buildTemplateContext(Aluno $aluno, Curso $curso, string $linkUrl, string $datas, int $vagasDisponiveis): array
    {
        return [
            '{{aluno_nome}}' => $aluno->nome_completo,
            '{{curso_nome}}' => $curso->nome,
            '{{datas}}' => $datas,
            '{{vagas}}' => (string) $vagasDisponiveis,
            '{{link}}' => $linkUrl,
        ];
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
        Aluno $aluno,
        Curso $curso,
        ?EventoCurso $evento,
        NotificationLink $link,
        NotificationType $notificationType
    ): void {
        NotificationLog::create([
            'aluno_id' => $aluno->id,
            'curso_id' => $curso->id,
            'evento_curso_id' => $evento?->id,
            'notificacao_link_id' => $link->id,
            'notification_type' => $notificationType->value,
            'canal' => $canal,
            'status' => $status,
            'erro' => $erro,
        ]);
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
        Aluno $aluno,
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
            ->where('aluno_id', $aluno->id)
            ->where('curso_id', $curso->id)
            ->where('notification_type', $notificationType->value)
            ->where('canal', $canal)
            ->where('status', '!=', 'blocked')
            ->whereDate('created_at', CarbonImmutable::today());

        if ($evento) {
            $query->where('evento_curso_id', $evento->id);
        }

        $count = $query->count();

        return $count >= $limiteDiario;
    }
}
