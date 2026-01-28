<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\EventoCurso;
use App\Services\MatriculaService;
use App\Services\ReminderService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app()->booted(function () {
    $schedule = app(Schedule::class);

    $schedule->call(function (MatriculaService $matriculaService) {
        $matriculaService->expirarMatriculasVencidas();
    })->hourly()->name('matriculas:expirar');

    $schedule->call(function (MatriculaService $matriculaService) {
        $matriculaService->enviarConfirmacoesAgendadas();
    })->dailyAt(config('app.scheduler.lembrete_horario'))->name('confirmacoes:enviar');

    $schedule->call(function (MatriculaService $matriculaService) {
        EventoCurso::query()
            ->where('ativo', true)
            ->each(function (EventoCurso $evento) use ($matriculaService) {
                $matriculaService->chamarListaEspera($evento);
            });
    })->hourly()->name('lista-espera:chamar');

    $schedule->call(function (MatriculaService $matriculaService) {
        $matriculaService->enviarNotificacoesVagasDisponiveis();
    })->hourly()->name('vagas-disponiveis:enviar');

    $schedule->call(function (ReminderService $reminderService) {
        $reminderService->enviarLembretes();
    })->dailyAt(config('app.scheduler.lembrete_horario'))->name('lembretes:enviar');
});
