<?php

namespace App\Providers;

use App\Models\Aluno;
use App\Models\Categoria;
use App\Models\Curso;
use App\Models\Estado;
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Models\Municipio;
use App\Models\SiteSection;
use App\Policies\AlunoPolicy;
use App\Policies\CategoriaPolicy;
use App\Policies\CursoPolicy;
use App\Policies\EstadoPolicy;
use App\Policies\EventoCursoPolicy;
use App\Policies\MatriculaPolicy;
use App\Policies\MunicipioPolicy;
use App\Policies\SiteSectionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Categoria::class => CategoriaPolicy::class,
        Curso::class => CursoPolicy::class,
        Estado::class => EstadoPolicy::class,
        EventoCurso::class => EventoCursoPolicy::class,
        Aluno::class => AlunoPolicy::class,
        Matricula::class => MatriculaPolicy::class,
        Municipio::class => MunicipioPolicy::class,
        SiteSection::class => SiteSectionPolicy::class,
    ];
}
