<?php

namespace App\Providers;

use App\Models\Aluno;
use App\Models\Categoria;
use App\Models\Configuracao;
use App\Models\Curso;
use App\Models\Estado;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Models\MediaAsset;
use App\Models\Municipio;
use App\Models\SiteSection;
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
        $helpersPath = app_path('helpers.php');

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->share('themeCssVariables', app(ThemeService::class)->getCssVariables());

        Curso::observe(AuditoriaObserver::class);
        Categoria::observe(AuditoriaObserver::class);
        Estado::observe(AuditoriaObserver::class);
        EventoCurso::observe(AuditoriaObserver::class);
        Aluno::observe(AuditoriaObserver::class);
        Matricula::observe(AuditoriaObserver::class);
        ListaEspera::observe(AuditoriaObserver::class);
        Configuracao::observe(AuditoriaObserver::class);
        SiteSection::observe(AuditoriaObserver::class);
        MediaAsset::observe(AuditoriaObserver::class);
        Municipio::observe(AuditoriaObserver::class);
    }
}
