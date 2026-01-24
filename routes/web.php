<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RelatorioAuditoriaController;
use App\Http\Controllers\Admin\RelatorioCursoController;
use App\Http\Controllers\Admin\RelatorioEventoController;
use App\Http\Controllers\Admin\RelatorioInscricaoController;
use App\Http\Controllers\Admin\RelatorioListaEsperaController;
use App\Http\Controllers\Admin\RelatorioMatriculaController;
use App\Http\Controllers\Admin\RelatorioNotificacaoController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', [\App\Http\Controllers\Public\PublicController::class, 'index'])
    ->name('public.home');

Route::get('/institucional', [\App\Http\Controllers\Public\PublicController::class, 'index'])
    ->name('public.institucional');
Route::get('/cursos', [\App\Http\Controllers\Public\PublicController::class, 'cursos'])
    ->name('public.cursos');

Route::get('/inscricao/cpf', [\App\Http\Controllers\Public\InscricaoController::class, 'cpfForm'])
    ->name('public.cpf');
Route::post('/inscricao/cpf', [\App\Http\Controllers\Public\InscricaoController::class, 'cpfSubmit'])
    ->name('public.cpf.submit');
Route::get('/inscricao/cadastro', [\App\Http\Controllers\Public\InscricaoController::class, 'cadastroForm'])
    ->name('public.cadastro');
Route::post('/inscricao/cadastro', [\App\Http\Controllers\Public\InscricaoController::class, 'cadastroStore'])
    ->name('public.cadastro.store');
Route::get('/inscricao/token/{token}', [\App\Http\Controllers\Public\InscricaoController::class, 'tokenRedirect'])
    ->name('public.inscricao.token');

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store']);
    });
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::resource('/cursos', \App\Http\Controllers\Admin\CursoController::class);
        Route::resource('/eventos', \App\Http\Controllers\Admin\EventoCursoController::class)
            ->parameters(['eventos' => 'evento']);
        Route::resource('/alunos', \App\Http\Controllers\Admin\AlunoController::class);
        Route::view('/usuarios', 'admin.usuarios.index')->name('usuarios.index');
        Route::get('/configuracoes', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'index'])
            ->name('configuracoes.index');
        Route::post('/configuracoes', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'update'])
            ->name('configuracoes.update');
        Route::get('/auditoria', [\App\Http\Controllers\Admin\AuditoriaController::class, 'index'])
            ->name('auditoria.index');
        Route::get('/notificacoes', [NotificationController::class, 'index'])
            ->name('notificacoes.index');
        Route::post('/notificacoes/disparar', [NotificationController::class, 'store'])
            ->name('notificacoes.store');
        Route::post('/notificacoes/preview', [NotificationController::class, 'preview'])
            ->name('notificacoes.preview');
        Route::get('/relatorios/matriculas', [RelatorioMatriculaController::class, 'index'])
            ->name('relatorios.matriculas.index');
        Route::get('/relatorios/matriculas/exportar', [RelatorioMatriculaController::class, 'export'])
            ->name('relatorios.matriculas.export');
        Route::get('/relatorios/notificacoes', [RelatorioNotificacaoController::class, 'index'])
            ->name('relatorios.notificacoes.index');
        Route::get('/relatorios/notificacoes/exportar', [RelatorioNotificacaoController::class, 'export'])
            ->name('relatorios.notificacoes.export');
        Route::get('/relatorios/inscricoes', [RelatorioInscricaoController::class, 'index'])
            ->name('relatorios.inscricoes.index');
        Route::get('/relatorios/inscricoes/exportar', [RelatorioInscricaoController::class, 'export'])
            ->name('relatorios.inscricoes.export');
        Route::get('/relatorios/lista-espera', [RelatorioListaEsperaController::class, 'index'])
            ->name('relatorios.lista-espera.index');
        Route::get('/relatorios/lista-espera/exportar', [RelatorioListaEsperaController::class, 'export'])
            ->name('relatorios.lista-espera.export');
        Route::get('/relatorios/eventos', [RelatorioEventoController::class, 'index'])
            ->name('relatorios.eventos.index');
        Route::get('/relatorios/eventos/exportar', [RelatorioEventoController::class, 'export'])
            ->name('relatorios.eventos.export');
        Route::get('/relatorios/cursos', [RelatorioCursoController::class, 'index'])
            ->name('relatorios.cursos.index');
        Route::get('/relatorios/cursos/exportar', [RelatorioCursoController::class, 'export'])
            ->name('relatorios.cursos.export');
        Route::get('/relatorios/auditoria', [RelatorioAuditoriaController::class, 'index'])
            ->name('relatorios.auditoria.index');
        Route::get('/relatorios/auditoria/exportar', [RelatorioAuditoriaController::class, 'export'])
            ->name('relatorios.auditoria.export');
        Route::view('/relatorios', 'admin.relatorios.index')
            ->name('relatorios.index');
    });

Route::middleware(['auth', 'role:aluno'])
    ->prefix('aluno')
    ->name('aluno.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/perfil', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'perfil'])
            ->name('perfil');
        Route::get('/inscricoes', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'inscricoes'])
            ->name('inscricoes');
        Route::get('/historico', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'historico'])
            ->name('historico');
        Route::get('/preferencias', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'preferencias'])
            ->name('preferencias');
    });
