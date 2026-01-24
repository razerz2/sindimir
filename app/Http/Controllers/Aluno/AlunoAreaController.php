<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AlunoAreaController extends Controller
{
    public function dashboard(): View
    {
        return view('aluno.dashboard');
    }

    public function perfil(): View
    {
        return view('aluno.perfil');
    }

    public function inscricoes(): View
    {
        return view('aluno.inscricoes');
    }

    public function historico(): View
    {
        return view('aluno.historico');
    }

    public function preferencias(): View
    {
        return view('aluno.preferencias');
    }
}
