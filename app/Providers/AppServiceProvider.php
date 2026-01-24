<?php

namespace App\Providers;

use App\Models\Aluno;
use App\Models\Configuracao;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Observers\AuditoriaObserver;
use App\Services\ThemeService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->share('themeCssVariables', app(ThemeService::class)->getCssVariables());

        Curso::observe(AuditoriaObserver::class);
        EventoCurso::observe(AuditoriaObserver::class);
        Aluno::observe(AuditoriaObserver::class);
        Matricula::observe(AuditoriaObserver::class);
        ListaEspera::observe(AuditoriaObserver::class);
        Configuracao::observe(AuditoriaObserver::class);
    }
}
