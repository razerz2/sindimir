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
        $linkUrl = route('public.cursos');
        $context = $this->buildTemplateContext($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

        $emailTemplate = $this->getTemplate($notificationType, 'email');
        $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

        $assunto = $emailTemplate && $emailTemplate->assunto
            ? $this->renderTemplate($emailTemplate->assunto, $context)
            : "Convite para {$curso->nome}";
        $corpoEmail = $emailTemplate
            ? $this->renderTemplate($emailTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);
        $textoWhatsApp = $whatsappTemplate
            ? $this->renderTemplate($whatsappTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

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
        NotificationType $notificationType = NotificationType::CURSO_DISPONIVEL
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
        $emailAtivo = (bool) $this->configuracaoService->get('notificacao.email_ativo', true);
        $whatsappAtivo = (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false);

        foreach ($colecaoAlunos as $aluno) {
            $link = $this->linkService->resolve($aluno, $curso, $destinoEvento, $notificationType);
            $linkUrl = route('public.inscricao.token', ['token' => $link->token]);
            $context = $this->buildTemplateContext($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

            $emailTemplate = $this->getTemplate($notificationType, 'email');
            $whatsappTemplate = $this->getTemplate($notificationType, 'whatsapp');

            $assunto = $emailTemplate && $emailTemplate->assunto
                ? $this->renderTemplate($emailTemplate->assunto, $context)
                : "Convite para {$curso->nome}";
        $mensagemEmail = $emailTemplate
            ? $this->renderTemplate($emailTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);
        $mensagemWhatsApp = $whatsappTemplate
            ? $this->renderTemplate($whatsappTemplate->conteudo, $context)
            : $this->buildFallbackMessage($aluno, $curso, $linkUrl, $datas, $vagasDisponiveis);

            if ($emailAtivo) {
                if ($aluno->email) {
                    if ($this->isRateLimited($aluno, $curso, $notificationType, 'email')) {
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
                } else {
                    $this->logAttempt('email', 'failed', 'Email do aluno não cadastrado.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                }
            }

            if ($whatsappAtivo) {
                if ($aluno->celular) {
                    if ($this->isRateLimited($aluno, $curso, $notificationType, 'whatsapp')) {
                        $this->logAttempt('whatsapp', 'blocked', 'Limite diário atingido.', $aluno, $curso, $destinoEvento, $link, $notificationType);
                    } else {
                        SendWhatsAppNotificationJob::dispatch(
                            $aluno->id,
                            $aluno->celular,
                            $curso->id,
                            $destinoEvento?->id,
                            $link->id,
                            $notificationType->value,
                            $mensagemWhatsApp
                        );
                    }
                } else {
                    $this->logAttempt('whatsapp', 'failed', 'WhatsApp do aluno não cadastrado.', $aluno, $curso, $destinoEvento, $link, $notificationType);
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
            ->where('status', StatusMatricula::Confirmada)
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
        int $vagasDisponiveis
    ): string {
        $linhas = [
            "Olá {$aluno->nome_completo},",
            '',
            "Curso: {$curso->nome}",
            "Datas: {$datas}",
            "Vagas disponíveis: {$vagasDisponiveis}",
            '',
            "Garanta sua vaga: {$linkUrl}",
        ];

        return implode(PHP_EOL, array_filter($linhas));
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

    private function isRateLimited(
        Aluno $aluno,
        Curso $curso,
        NotificationType $notificationType,
        string $canal
    ): bool {
        $count = NotificationLog::query()
            ->where('aluno_id', $aluno->id)
            ->where('curso_id', $curso->id)
            ->where('notification_type', $notificationType->value)
            ->where('canal', $canal)
            ->where('status', '!=', 'blocked')
            ->whereDate('created_at', CarbonImmutable::today())
            ->count();

        return $count >= 2;
    }
}
