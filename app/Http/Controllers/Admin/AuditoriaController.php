<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(): View
    {
        $registros = Auditoria::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.auditoria.index', compact('registros'));
    }
}
