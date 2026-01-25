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
        $themeFavicon = null;
        $themeLogo = null;
        $themeBackgroundImage = null;
        $themeBackgroundOverlay = 'rgba(255,255,255,0.85)';
        $themeBackgroundPosition = 'center';
        $themeBackgroundSize = 'cover';

        if (function_exists('config_db')) {
            $themeFavicon = config_db('tema.favicon');
            $themeLogo = config_db('tema.logo');
            $themeBackgroundImage = config_db('tema.background_main_imagem');
            $themeBackgroundOverlay = config_db('tema.background_main_overlay', $themeBackgroundOverlay);
            $themeBackgroundPosition = config_db('tema.background_main_posicao', $themeBackgroundPosition);
            $themeBackgroundSize = config_db('tema.background_main_tamanho', $themeBackgroundSize);
        }

        view()->share(compact(
            'themeFavicon',
            'themeLogo',
            'themeBackgroundImage',
            'themeBackgroundOverlay',
            'themeBackgroundPosition',
            'themeBackgroundSize'
        ));

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
