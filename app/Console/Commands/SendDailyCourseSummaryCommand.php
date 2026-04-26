<?php

namespace App\Console\Commands;

use App\Services\ConfiguracaoService;
use App\Services\UserCourseNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDailyCourseSummaryCommand extends Command
{
    protected $signature = 'cursos:enviar-resumo-diario {--force : Ignora horário e idempotência diária}';

    protected $description = 'Envia o resumo diário de cursos ativos para usuários administrativos.';

    private const CONFIG_ROOT = 'notificacao.auto.usuario_resumo_diario_cursos';
    private const LAST_SENT_DATE_KEY = 'notificacao.auto.usuario_resumo_diario_cursos.ultimo_envio_data';
    private const LAST_SENT_AT_KEY = 'notificacao.auto.usuario_resumo_diario_cursos.ultimo_envio_em';

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly UserCourseNotificationService $userCourseNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);
        $force = (bool) $this->option('force');

        if (! $force && ! (bool) $this->configuracaoService->get(self::CONFIG_ROOT . '.ativo', false)) {
            $this->info('Resumo diário desativado em configurações.');

            return self::SUCCESS;
        }

        $horarioConfigurado = $this->normalizeHour(
            (string) $this->configuracaoService->get(self::CONFIG_ROOT . '.horario_envio', '08:00')
        );
        $horaAtual = $now->format('H:i');

        if (! $force && $horaAtual !== $horarioConfigurado) {
            $this->info("Fora do horário configurado ({$horarioConfigurado}). Hora atual: {$horaAtual}.");

            return self::SUCCESS;
        }

        $today = $now->toDateString();
        $lastSentDate = (string) $this->configuracaoService->get(self::LAST_SENT_DATE_KEY, '');
        if (! $force && $lastSentDate === $today) {
            $this->info("Resumo diário já enviado em {$today}.");

            return self::SUCCESS;
        }

        try {
            $resultado = $this->userCourseNotificationService->sendDailyCoursesSummary($now);
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar resumo diário de cursos.', [
                'erro' => $exception->getMessage(),
            ]);

            $this->error('Falha ao enviar resumo diário de cursos.');

            return self::FAILURE;
        }

        if (! $resultado['sent']) {
            $this->info('Resumo diário não enviado. Motivo: ' . ($resultado['reason'] ?? 'indefinido'));

            return self::SUCCESS;
        }

        $this->configuracaoService->set(self::LAST_SENT_DATE_KEY, $today, 'Data do último envio do resumo diário de cursos');
        $this->configuracaoService->set(
            self::LAST_SENT_AT_KEY,
            $now->toDateTimeString(),
            'Data/hora do último envio do resumo diário de cursos'
        );

        $this->info(sprintf(
            'Resumo diário enviado (%d evento(s), %d destinatário(s)).',
            (int) ($resultado['events_count'] ?? 0),
            (int) ($resultado['recipients_count'] ?? 0)
        ));

        return self::SUCCESS;
    }

    private function normalizeHour(string $value): string
    {
        $value = trim($value);
        if ($value === '' || ! preg_match('/^\d{2}:\d{2}$/', $value)) {
            return '08:00';
        }

        return $value;
    }
}
