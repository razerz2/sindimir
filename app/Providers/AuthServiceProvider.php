<?php

namespace App\Providers;

use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\Matricula;
use App\Policies\AlunoPolicy;
use App\Policies\CursoPolicy;
use App\Policies\EventoCursoPolicy;
use App\Policies\MatriculaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Curso::class => CursoPolicy::class,
        EventoCurso::class => EventoCursoPolicy::class,
        Aluno::class => AlunoPolicy::class,
        Matricula::class => MatriculaPolicy::class,
    ];
}
