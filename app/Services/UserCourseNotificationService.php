<?php

namespace App\Services;

use App\Enums\LegacyNotificationType;
use App\Enums\StatusMatricula;
use App\Enums\UserRole;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Support\Cpf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserCourseNotificationService
{
    private const DESTINATARIO_USUARIO = 'usuario';
    private const RESUMO_DIARIO_CONFIG_ROOT = 'notificacao.auto.usuario_resumo_diario_cursos';

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService
    ) {
    }

    public function notifyEnrollment(Matricula $matricula, EventoCurso $evento): void
    {
        $this->dispatchForEvent(
            $matricula,
            $evento,
            LegacyNotificationType::USUARIO_INSCRICAO_CURSO,
            'notificacao.auto.usuario_inscricao_curso',
            false
        );
    }

    public function notifyCancellation(Matricula $matricula, EventoCurso $evento): void
    {
        $this->dispatchForEvent(
            $matricula,
            $evento,
            LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO,
            'notificacao.auto.usuario_cancelamento_curso',
            true
        );
    }

    /**
     * @return array{
     *   sent: bool,
     *   events_count: int,
     *   recipients_count: int,
     *   email_enabled: bool,
     *   whatsapp_enabled: bool,
     *   reason: string
     * }
     */
    public function sendDailyCoursesSummary(?CarbonImmutable $referenceDateTime = null): array
    {
        $referenceDateTime ??= CarbonImmutable::now((string) config('app.timezone', 'UTC'));
        if (! $this->isEventEnabled(self::RESUMO_DIARIO_CONFIG_ROOT)) {
            return $this->buildSummaryResult(false, 0, 0, false, false, 'event_disabled');
        }

        [$emailAtivo, $whatsappAtivo] = $this->resolveChannels(self::RESUMO_DIARIO_CONFIG_ROOT);
        if (! $emailAtivo && ! $whatsappAtivo) {
            return $this->buildSummaryResult(false, 0, 0, false, false, 'channels_disabled');
        }

        $eventosAtivos = $this->loadActiveEventsWithStats();
        if ($eventosAtivos->isEmpty()) {
            Log::info('Resumo diário de cursos ativos ignorado: não há eventos ativos.');

            return $this->buildSummaryResult(false, 0, 0, $emailAtivo, $whatsappAtivo, 'no_active_events');
        }

        $destinatarios = $this->resolveRecipients();
        if ($destinatarios->isEmpty()) {
            Log::info('Resumo diário de cursos ativos ignorado: não há usuários administrativos.');

            return $this->buildSummaryResult(false, $eventosAtivos->count(), 0, $emailAtivo, $whatsappAtivo, 'no_recipients');
        }

        $context = $this->buildDailySummaryContext($eventosAtivos, $referenceDateTime);
        $notificationType = LegacyNotificationType::USUARIO_RESUMO_DIARIO_CURSOS;
        $emailTemplate = $this->getTemplate($notificationType, 'email');
        $whatsAppTemplate = $this->getTemplate($notificationType, 'whatsapp');
        $assuntoEmail = $this->resolveDailySummarySubject($context, $emailTemplate?->assunto);
        $mensagemEmail = $this->resolveDailySummaryMessage($context, $emailTemplate?->conteudo);
        $mensagemWhatsApp = $this->resolveDailySummaryMessage($context, $whatsAppTemplate?->conteudo);
        $eventoReferencia = $eventosAtivos->first();

        $emailsEnviados = [];
        $whatsAppsEnviados = [];

        foreach ($destinatarios as $usuario) {
            if ($emailAtivo) {
                $this->dispatchEmail(
                    $usuario,
                    $notificationType,
                    $eventoReferencia,
                    $assuntoEmail,
                    $mensagemEmail,
                    $emailsEnviados
                );
            }

            if ($whatsappAtivo) {
                $this->dispatchWhatsApp(
                    $usuario,
                    $notificationType,
                    $eventoReferencia,
                    $mensagemWhatsApp,
                    $whatsAppsEnviados
                );
            }
        }

        return $this->buildSummaryResult(
            true,
            $eventosAtivos->count(),
            $destinatarios->count(),
            $emailAtivo,
            $whatsappAtivo,
            'sent'
        );
    }

    private function dispatchForEvent(
        Matricula $matricula,
        EventoCurso $evento,
        string $notificationType,
        string $configRoot,
        bool $isCancellation
    ): void {
        try {
            if (! $this->isEventEnabled($configRoot)) {
                return;
            }

            [$emailAtivo, $whatsappAtivo] = $this->resolveChannels($configRoot);
            if (! $emailAtivo && ! $whatsappAtivo) {
                return;
            }

            $matricula->loadMissing('aluno');
            $evento->loadMissing('curso');

            if (! $matricula->aluno || ! $evento->curso) {
                Log::warning('Notificação interna para usuários não enviada por dados incompletos.', [
                    'notification_type' => $notificationType,
                    'matricula_id' => $matricula->id,
                    'evento_curso_id' => $evento->id,
                ]);

                return;
            }

            $destinatarios = $this->resolveRecipients();
            if ($destinatarios->isEmpty()) {
                Log::info('Nenhum usuário administrativo encontrado para notificação interna.', [
                    'notification_type' => $notificationType,
                ]);

                return;
            }

            $context = $this->buildContext($matricula, $evento, $isCancellation);
            $emailTemplate = $this->getTemplate($notificationType, 'email');
            $whatsAppTemplate = $this->getTemplate($notificationType, 'whatsapp');

            $assuntoEmail = $this->resolveSubject($notificationType, $context, $emailTemplate?->assunto);
            $mensagemEmail = $this->resolveMessage(
                $notificationType,
                $context,
                $emailTemplate?->conteudo,
                $isCancellation
            );
            $mensagemWhatsApp = $this->resolveMessage(
                $notificationType,
                $context,
                $whatsAppTemplate?->conteudo,
                $isCancellation
            );

            $emailsEnviados = [];
            $whatsAppsEnviados = [];

            foreach ($destinatarios as $usuario) {
                if ($emailAtivo) {
                    $this->dispatchEmail(
                        $usuario,
                        $notificationType,
                        $evento,
                        $assuntoEmail,
                        $mensagemEmail,
                        $emailsEnviados
                    );
                }

                if ($whatsappAtivo) {
                    $this->dispatchWhatsApp(
                        $usuario,
                        $notificationType,
                        $evento,
                        $mensagemWhatsApp,
                        $whatsAppsEnviados
                    );
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Falha inesperada ao disparar notificação interna para usuários.', [
                'notification_type' => $notificationType,
                'matricula_id' => $matricula->id,
                'evento_curso_id' => $evento->id,
                'erro' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{
     *   sent: bool,
     *   events_count: int,
     *   recipients_count: int,
     *   email_enabled: bool,
     *   whatsapp_enabled: bool,
     *   reason: string
     * }
     */
    private function buildSummaryResult(
        bool $sent,
        int $eventsCount,
        int $recipientsCount,
        bool $emailEnabled,
        bool $whatsappEnabled,
        string $reason
    ): array {
        return [
            'sent' => $sent,
            'events_count' => $eventsCount,
            'recipients_count' => $recipientsCount,
            'email_enabled' => $emailEnabled,
            'whatsapp_enabled' => $whatsappEnabled,
            'reason' => $reason,
        ];
    }

    private function dispatchEmail(
        User $usuario,
        string $notificationType,
        EventoCurso $evento,
        string $assunto,
        string $mensagem,
        array &$emailsEnviados
    ): void {
        $email = trim((string) $usuario->email);
        $emailKey = strtolower($email);

        if (! $this->isValidEmail($email)) {
            Log::info('Usuário ignorado em notificação interna por e-mail inválido.', [
                'notification_type' => $notificationType,
                'user_id' => $usuario->id,
            ]);

            return;
        }

        if (isset($emailsEnviados[$emailKey])) {
            return;
        }

        try {
            SendEmailNotificationJob::dispatch(
                self::DESTINATARIO_USUARIO,
                null,
                $usuario->display_name,
                $email,
                $evento->curso_id,
                $evento->id,
                null,
                $notificationType,
                $assunto,
                $mensagem
            );

            $emailsEnviados[$emailKey] = true;
        } catch (Throwable $exception) {
            Log::warning('Falha ao enfileirar notificação interna por e-mail.', [
                'notification_type' => $notificationType,
                'user_id' => $usuario->id,
                'evento_curso_id' => $evento->id,
                'erro' => $exception->getMessage(),
            ]);
        }
    }

    private function dispatchWhatsApp(
        User $usuario,
        string $notificationType,
        EventoCurso $evento,
        string $mensagem,
        array &$whatsAppsEnviados
    ): void {
        $numero = $this->normalizeWhatsapp($usuario->whatsapp);
        if (! $numero) {
            Log::info('Usuário ignorado em notificação interna por WhatsApp inválido.', [
                'notification_type' => $notificationType,
                'user_id' => $usuario->id,
            ]);

            return;
        }

        if (isset($whatsAppsEnviados[$numero])) {
            return;
        }

        try {
            SendWhatsAppNotificationJob::dispatch(
                self::DESTINATARIO_USUARIO,
                null,
                $usuario->display_name,
                $numero,
                $evento->curso_id,
                $evento->id,
                null,
                $notificationType,
                $mensagem
            );

            $whatsAppsEnviados[$numero] = true;
        } catch (Throwable $exception) {
            Log::warning('Falha ao enfileirar notificação interna por WhatsApp.', [
                'notification_type' => $notificationType,
                'user_id' => $usuario->id,
                'evento_curso_id' => $evento->id,
                'erro' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveRecipients(): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Usuario->value])
            ->orderBy('id')
            ->get(['id', 'name', 'nome_exibicao', 'email', 'whatsapp', 'role']);
    }

    /**
     * @return array{bool, bool}
     */
    private function resolveChannels(string $configRoot): array
    {
        $emailAtivo = (bool) $this->configuracaoService->get("{$configRoot}.canal.email", true);
        $whatsappAtivo = (bool) $this->configuracaoService->get("{$configRoot}.canal.whatsapp", false);
        $emailGlobalAtivo = (bool) $this->configuracaoService->get('notificacao.email_ativo', true);
        $whatsappGlobalAtivo = (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false);

        return [
            $emailAtivo && $emailGlobalAtivo,
            $whatsappAtivo && $whatsappGlobalAtivo,
        ];
    }

    private function isEventEnabled(string $configRoot): bool
    {
        return (bool) $this->configuracaoService->get("{$configRoot}.ativo", false);
    }

    private function getTemplate(string $notificationType, string $canal): ?NotificationTemplate
    {
        return NotificationTemplate::query()
            ->where('notification_type', $notificationType)
            ->where('canal', $canal)
            ->where('ativo', true)
            ->first();
    }

    private function resolveSubject(string $notificationType, array $context, ?string $templateSubject): string
    {
        if ($templateSubject) {
            return $this->renderTemplate($templateSubject, $context);
        }

        return $notificationType === LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO
            ? "Cancelamento de inscrição/matrícula: {$context['{{curso_nome}}']}"
            : "Nova inscrição em curso: {$context['{{curso_nome}}']}";
    }

    private function resolveMessage(
        string $notificationType,
        array $context,
        ?string $template,
        bool $isCancellation
    ): string {
        if ($template) {
            $mensagem = $this->renderTemplate($template, $context);
            if (! str_contains($mensagem, '{{')) {
                return $mensagem;
            }
        }

        $linhas = [
            'Olá,',
            '',
            $isCancellation ? 'Cancelamento registrado.' : 'Nova inscrição registrada.',
            "Aluno: {$context['{{aluno_nome}}']}",
            "CPF: {$context['{{aluno_cpf}}']}",
            "Curso: {$context['{{curso_nome}}']}",
            "Data(s): {$context['{{datas}}']}",
            "Horário: {$context['{{horario}}']}",
            "Status da matrícula: {$context['{{status_matricula}}']}",
            $isCancellation
                ? "Data/Hora do cancelamento: {$context['{{data_cancelamento}}']}"
                : "Data/Hora da inscrição: {$context['{{data_inscricao}}']}",
            "Acesse no administrativo: {$context['{{link_admin}}']}",
        ];

        return implode(PHP_EOL, $linhas);
    }

    private function resolveDailySummarySubject(array $context, ?string $templateSubject): string
    {
        if ($templateSubject) {
            return $this->renderTemplate($templateSubject, $context);
        }

        return "Resumo diário de cursos ativos - {$context['{{data_resumo}}']}";
    }

    private function resolveDailySummaryMessage(array $context, ?string $template): string
    {
        if ($template) {
            $mensagem = $this->renderTemplate($template, $context);
            if (! str_contains($mensagem, '{{')) {
                return $mensagem;
            }
        }

        return implode(PHP_EOL, [
            'Olá,',
            '',
            "Resumo diário de cursos ativos em {$context['{{data_resumo}}']}.",
            "Total de eventos ativos: {$context['{{total_eventos}}']}",
            '',
            $context['{{resumo_cursos}}'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function buildContext(Matricula $matricula, EventoCurso $evento, bool $isCancellation): array
    {
        $agora = CarbonImmutable::now();
        $aluno = $matricula->aluno;
        $status = $matricula->status instanceof StatusMatricula
            ? $matricula->status->value
            : (string) $matricula->status;
        $cpf = Cpf::format((string) ($aluno->cpf ?? ''));
        $dataInscricao = $isCancellation
            ? (string) ($matricula->created_at?->format('d/m/Y H:i') ?? '')
            : $agora->format('d/m/Y H:i');
        $dataCancelamento = $isCancellation
            ? $agora->format('d/m/Y H:i')
            : '';

        return [
            '{{aluno_nome}}' => (string) $aluno->nome_completo,
            '{{aluno_cpf}}' => $cpf !== '' ? $cpf : 'Não informado',
            '{{curso_nome}}' => (string) $evento->curso->nome,
            '{{datas}}' => $this->formatDates($evento),
            '{{horario}}' => $this->formatSchedule($evento),
            '{{status_matricula}}' => $this->formatStatus($status),
            '{{data_inscricao}}' => $dataInscricao,
            '{{data_cancelamento}}' => $dataCancelamento,
            '{{link_admin}}' => $this->resolveAdminLink($evento),
        ];
    }

    private function formatDates(EventoCurso $evento): string
    {
        $inicio = $evento->data_inicio?->format('d/m/Y');
        $fim = $evento->data_fim?->format('d/m/Y');

        if ($inicio && $fim && $inicio !== $fim) {
            return "{$inicio} a {$fim}";
        }

        return $inicio ?? 'Data ainda não informada';
    }

    /**
     * @return Collection<int, EventoCurso>
     */
    private function loadActiveEventsWithStats(): Collection
    {
        return EventoCurso::query()
            ->with('curso')
            ->withCount([
                'matriculas as inscritos_count' => function ($query): void {
                    $query->whereIn('status', [
                        StatusMatricula::Confirmada->value,
                        StatusMatricula::Pendente->value,
                    ]);
                },
            ])
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->orderBy('data_inicio')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param Collection<int, EventoCurso> $eventos
     * @return array<string, string>
     */
    private function buildDailySummaryContext(Collection $eventos, CarbonImmutable $referenceDateTime): array
    {
        $cancelamentosPorEvento = Matricula::query()
            ->selectRaw('evento_curso_id, COUNT(*) AS total')
            ->where('status', StatusMatricula::Cancelada->value)
            ->whereDate('updated_at', $referenceDateTime->toDateString())
            ->groupBy('evento_curso_id')
            ->pluck('total', 'evento_curso_id');

        $linhas = [];
        $posicao = 1;

        foreach ($eventos as $evento) {
            $curso = $evento->curso;
            if (! $curso) {
                continue;
            }

            $inscritos = (int) ($evento->inscritos_count ?? 0);
            $vagasTotais = (int) $curso->limite_vagas;
            $vagasTexto = $vagasTotais > 0 ? (string) $vagasTotais : 'Ilimitadas';
            $vagasDisponiveisTexto = $vagasTotais > 0
                ? (string) max(0, $vagasTotais - $inscritos)
                : 'Ilimitadas';
            $cancelamentosHoje = (int) ($cancelamentosPorEvento[$evento->id] ?? 0);
            $linhas[] = implode(PHP_EOL, [
                "{$posicao}) Curso: {$curso->nome}",
                "Datas/Horários: {$this->formatDates($evento)} | {$this->formatSchedule($evento)}",
                "Inscritos: {$inscritos}",
                "Vagas totais: {$vagasTexto}",
                "Vagas disponíveis: {$vagasDisponiveisTexto}",
                "Cancelamentos do dia: {$cancelamentosHoje}",
                "Status do curso: {$this->resolveCourseStatus($evento, $referenceDateTime)}",
                "Link administrativo: {$this->resolveAdminLink($evento)}",
            ]);
            $posicao++;
        }

        return [
            '{{data_resumo}}' => $referenceDateTime->format('d/m/Y'),
            '{{total_eventos}}' => (string) count($linhas),
            '{{resumo_cursos}}' => implode(PHP_EOL . PHP_EOL, $linhas),
        ];
    }

    private function resolveCourseStatus(EventoCurso $evento, CarbonImmutable $referenceDateTime): string
    {
        if (! $evento->ativo || ! $evento->curso?->ativo) {
            return 'Inativo';
        }

        $today = $referenceDateTime->toDateString();
        $inicio = $evento->data_inicio?->toDateString();
        $fim = $evento->data_fim?->toDateString();

        if ($inicio && $today < $inicio) {
            return 'Programado';
        }

        if ($fim && $today > $fim) {
            return 'Encerrado';
        }

        return 'Ativo';
    }

    private function formatSchedule(EventoCurso $evento): string
    {
        $inicio = $this->formatHour((string) ($evento->horario_inicio ?? ''));
        $fim = $this->formatHour((string) ($evento->horario_fim ?? ''));

        if ($inicio !== '' && $fim !== '') {
            return "{$inicio} às {$fim}";
        }

        return $inicio !== '' ? $inicio : 'Não informado';
    }

    private function formatHour(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return '';
        }

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    private function formatStatus(string $status): string
    {
        return match (strtolower($status)) {
            StatusMatricula::Pendente->value => 'Pendente',
            StatusMatricula::Confirmada->value => 'Confirmada',
            StatusMatricula::Cancelada->value => 'Cancelada',
            StatusMatricula::Expirada->value => 'Expirada',
            default => ucfirst($status),
        };
    }

    private function resolveAdminLink(EventoCurso $evento): string
    {
        try {
            return route('admin.eventos.inscritos', ['evento' => $evento->id]);
        } catch (Throwable) {
            return '';
        }
    }

    private function renderTemplate(string $template, array $context): string
    {
        return strtr($template, $context);
    }

    private function isValidEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function normalizeWhatsapp(?string $whatsapp): ?string
    {
        if (! $whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $whatsapp);
        if (! $digits) {
            return null;
        }

        $numeroSemPais = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
        if (! preg_match('/^\d{10,11}$/', $numeroSemPais)) {
            return null;
        }

        return '55' . $numeroSemPais;
    }
}
