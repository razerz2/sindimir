<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventoCurso;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function index(): View
    {
        return view('public.institucional.index');
    }

    public function institucional(): View
    {
        return $this->index();
    }

    public function cursos(): View
    {
        $eventos = EventoCurso::query()
            ->with('curso')
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->orderBy('data_inicio')
            ->paginate(12);

        return view('public.cursos', compact('eventos'));
    }
}
