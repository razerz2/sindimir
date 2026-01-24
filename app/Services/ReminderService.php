<?php

namespace App\Services;

use App\Enums\StatusMatricula;
use App\Models\EventoCurso;
use Carbon\CarbonImmutable;

class ReminderService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function enviarLembretes(): int
    {
        $diasAntes = (int) config('app.scheduler.lembrete_dias_antes', 1);
        $agora = CarbonImmutable::now()->startOfDay();
        $limite = $agora->addDays($diasAntes)->endOfDay();

        $eventos = EventoCurso::query()
            ->with([
                'curso',
                'matriculas' => function ($query) {
                    $query->where('status', StatusMatricula::Confirmada)
                        ->with('aluno');
                },
            ])
            ->whereBetween('data_inicio', [$agora, $limite])
            ->where('ativo', true)
            ->get();

        $notificacoes = 0;

        foreach ($eventos as $evento) {
            foreach ($evento->matriculas as $matricula) {
                $aluno = $matricula->aluno;

                if (! $aluno) {
                    continue;
                }

                $mensagem = $this->buildMensagem($evento->curso?->nome, $evento->data_inicio, $evento->local_realizacao);
                $assunto = (string) config('app.scheduler.lembrete_email_assunto', 'Lembrete de curso');

                if ($aluno->email) {
                    $this->notificationService->enviarEmail($aluno->email, $assunto, $mensagem);
                    $notificacoes++;
                }

                if ($aluno->celular) {
                    $this->notificationService->enviarWhatsApp($aluno->celular, $mensagem);
                    $notificacoes++;
                }
            }
        }

        return $notificacoes;
    }

    private function buildMensagem(?string $curso, CarbonImmutable $dataInicio, string $local): string
    {
        $template = (string) config('app.scheduler.lembrete_mensagem');

        if ($template === '') {
            $template = 'Lembrete: o curso {curso} inicia em {data_inicio} no local {local}.';
        }

        return str_replace(
            ['{curso}', '{data_inicio}', '{local}'],
            [$curso ?? 'Curso', $dataInicio->format('d/m/Y'), $local],
            $template
        );
    }
}
