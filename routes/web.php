<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\EstadoController;
use App\Http\Controllers\Admin\GoogleContactsController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\MunicipioController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RelatorioAuditoriaController;
use App\Http\Controllers\Admin\RelatorioCursoController;
use App\Http\Controllers\Admin\RelatorioEventoController;
use App\Http\Controllers\Admin\RelatorioInscricaoController;
use App\Http\Controllers\Admin\RelatorioListaEsperaController;
use App\Http\Controllers\Admin\RelatorioMatriculaController;
use App\Http\Controllers\Admin\RelatorioNotificacaoController;
use App\Http\Controllers\Admin\SiteSectionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Public\ContatoController;

Route::get('/', [\App\Http\Controllers\Public\PublicController::class, 'index'])
    ->name('public.home');

Route::get('/institucional', [\App\Http\Controllers\Public\PublicController::class, 'index'])
    ->name('public.institucional');
Route::get('/cursos', [\App\Http\Controllers\Public\PublicController::class, 'cursos'])
    ->name('public.cursos');
Route::get('/contato', [ContatoController::class, 'index'])
    ->name('public.contato');
Route::post('/contato', [ContatoController::class, 'enviar'])
    ->name('public.contato.enviar');

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
Route::get('/inscricao/confirmar/{token}', [\App\Http\Controllers\Public\InscricaoController::class, 'confirmarInscricao'])
    ->name('public.inscricao.confirmar');
Route::post('/inscricao/confirmar/{token}/sim', [\App\Http\Controllers\Public\InscricaoController::class, 'confirmarInscricaoSim'])
    ->name('public.inscricao.confirmar.sim');
Route::post('/inscricao/confirmar/{token}/nao', [\App\Http\Controllers\Public\InscricaoController::class, 'cancelarInscricaoNao'])
    ->name('public.inscricao.confirmar.nao');
Route::get('/inscricao/cancelada/{token}', [\App\Http\Controllers\Public\InscricaoController::class, 'cancelarInscricaoPagina'])
    ->name('public.inscricao.cancelada');
Route::get('/inscricao/realizada', [\App\Http\Controllers\Public\InscricaoController::class, 'inscricaoRealizada'])
    ->name('public.inscricao.realizada');
Route::get('/matricula/{token}', [\App\Http\Controllers\Public\InscricaoController::class, 'visualizarMatricula'])
    ->name('public.matricula.visualizar');
Route::post('/matricula/{token}/cancelar', [\App\Http\Controllers\Public\InscricaoController::class, 'cancelarMatriculaPublica'])
    ->name('public.matricula.cancelar');

Route::get('/catalogos/estados/{estado}/municipios', [MunicipioController::class, 'byEstado'])
    ->name('public.catalogo.estados.municipios');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    });
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');
    Route::get('/logout', [LoginController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout.get');
});

Route::prefix('aluno')->name('aluno.')->middleware('guest:aluno')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('guest:admin,aluno')->group(function () {
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/reenviar', [TwoFactorController::class, 'resend'])->name('2fa.resend');
});

Route::middleware(['auth:admin', 'role:admin,usuario', 'module-access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard');
        Route::resource('/cursos', \App\Http\Controllers\Admin\CursoController::class);
        Route::resource('/catalogos/categorias', CategoriaController::class)
            ->names('catalogo.categorias')
            ->parameters(['categorias' => 'categoria']);
        Route::post('/catalogos/categorias/{categoria}/toggle', [CategoriaController::class, 'toggle'])
            ->name('catalogo.categorias.toggle');
        Route::resource('/catalogos/estados', EstadoController::class)
            ->names('catalogo.estados')
            ->parameters(['estados' => 'estado']);
        Route::post('/catalogos/estados/{estado}/toggle', [EstadoController::class, 'toggle'])
            ->name('catalogo.estados.toggle');
        Route::resource('/catalogos/municipios', MunicipioController::class)
            ->names('catalogo.municipios')
            ->parameters(['municipios' => 'municipio']);
        Route::post('/catalogos/municipios/{municipio}/toggle', [MunicipioController::class, 'toggle'])
            ->name('catalogo.municipios.toggle');
        Route::get('/catalogos/estados/{estado}/municipios', [MunicipioController::class, 'byEstado'])
            ->name('catalogo.estados.municipios');
        Route::get('/eventos/{evento}/inscritos', [\App\Http\Controllers\Admin\EventoCursoController::class, 'inscritos'])
            ->name('eventos.inscritos');
        Route::resource('/eventos', \App\Http\Controllers\Admin\EventoCursoController::class)
            ->parameters(['eventos' => 'evento']);
        Route::post('/matriculas/{matricula}/cancelar', [\App\Http\Controllers\Admin\MatriculaController::class, 'cancelar'])
            ->name('matriculas.cancelar');
        Route::post('/lista-espera/{lista}/subir', [\App\Http\Controllers\Admin\ListaEsperaController::class, 'subir'])
            ->name('lista-espera.subir');
        Route::post('/lista-espera/{lista}/descer', [\App\Http\Controllers\Admin\ListaEsperaController::class, 'descer'])
            ->name('lista-espera.descer');
        Route::post('/lista-espera/{lista}/inscrever', [\App\Http\Controllers\Admin\ListaEsperaController::class, 'inscrever'])
            ->name('lista-espera.inscrever');
        Route::delete('/lista-espera/{lista}', [\App\Http\Controllers\Admin\ListaEsperaController::class, 'remover'])
            ->name('lista-espera.remover');
        Route::resource('/alunos', \App\Http\Controllers\Admin\AlunoController::class);
        Route::get('/usuarios/{user}/senha', [UserController::class, 'editPassword'])
            ->name('usuarios.senha.edit');
        Route::put('/usuarios/{user}/senha', [UserController::class, 'updatePassword'])
            ->name('usuarios.senha.update');
        Route::resource('/usuarios', UserController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
            ->parameters(['usuarios' => 'user']);
        Route::get('/configuracoes', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'index'])
            ->name('configuracoes.index');
        Route::post('/configuracoes', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'update'])
            ->name('configuracoes.update');
        Route::post('/configuracoes/whatsapp/testar', [\App\Http\Controllers\Admin\ConfiguracaoController::class, 'testarWhatsapp'])
            ->name('configuracoes.whatsapp.testar');
        Route::get('/google/contatos/conectar', [GoogleContactsController::class, 'connect'])
            ->name('google.contacts.connect');
        Route::get('/google/contatos/callback', [GoogleContactsController::class, 'callback'])
            ->name('google.contacts.callback');
        Route::post('/google/contatos/importar', [GoogleContactsController::class, 'import'])
            ->name('google.contacts.import');
        Route::post('/google/contatos/desconectar', [GoogleContactsController::class, 'disconnect'])
            ->name('google.contacts.disconnect');
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
        Route::get('site', [SiteSectionController::class, 'index'])
            ->name('site.index');
        Route::resource('site/sections', SiteSectionController::class)
            ->names('site.sections')
            ->parameters(['sections' => 'section']);
        Route::post('site/sections/reorder', [SiteSectionController::class, 'reorder'])
            ->name('site.sections.reorder');
        Route::post('site/sections/{section}/duplicate', [SiteSectionController::class, 'duplicate'])
            ->name('site.sections.duplicate');
        Route::post('site/sections/{section}/toggle', [SiteSectionController::class, 'toggle'])
            ->name('site.sections.toggle');
        Route::get('site/media', [MediaAssetController::class, 'index'])
            ->name('site.media.index');
        Route::post('site/media', [MediaAssetController::class, 'store'])
            ->name('site.media.store');
    });

Route::middleware(['auth:aluno', 'role:aluno'])
    ->prefix('aluno')
    ->name('aluno.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'dashboard'])
            ->name('dashboard');
        Route::get('/perfil', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'perfil'])
            ->name('perfil');
        Route::put('/perfil', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'perfilUpdate'])
            ->name('perfil.update');
        Route::get('/inscricoes', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'inscricoes'])
            ->name('inscricoes');
        Route::post('/inscricoes/{eventoCurso}', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'inscrever'])
            ->name('inscricoes.store');
        Route::get('/historico', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'historico'])
            ->name('historico');
        Route::get('/preferencias', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'preferencias'])
            ->name('preferencias');
        Route::put('/preferencias', [\App\Http\Controllers\Aluno\AlunoAreaController::class, 'preferenciasUpdate'])
            ->name('preferencias.update');
    });

Route::post('/aluno/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:aluno')
    ->name('aluno.logout');
Route::get('/aluno/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:aluno')
    ->name('aluno.logout.get');
