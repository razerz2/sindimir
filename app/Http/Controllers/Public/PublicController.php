<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EventoCurso;
use App\Models\SiteSection;
use App\Services\ConfiguracaoService;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function index(ConfiguracaoService $configuracaoService): View
    {
        $sections = SiteSection::ativos()
            ->ordenados()
            ->get()
            ->keyBy('slug');

        return view('public.institucional.index', [
            'hero' => $sections->get('hero'),
            'sobre' => $sections->get('sobre'),
            'solucoes' => $sections->get('solucoes'),
            'diferenciais' => $sections->get('diferenciais'),
            'cta' => $sections->get('contato'),
            'footer' => [
                'titulo' => $configuracaoService->get('site.footer.titulo', 'Sindimir'),
                'descricao' => $configuracaoService->get(
                    'site.footer.descricao',
                    'Solucoes digitais para capacitacao, eventos e desenvolvimento do setor metal mecanico.'
                ),
                'contato_titulo' => $configuracaoService->get('site.footer.contato_titulo', 'Contato'),
                'contato_email' => $configuracaoService->get('site.footer.contato_email', 'contato@sindimir.org'),
                'contato_telefone' => $configuracaoService->get('site.footer.contato_telefone', '(00) 0000-0000'),
                'endereco_titulo' => $configuracaoService->get('site.footer.endereco_titulo', 'Endereco'),
                'endereco_linha1' => $configuracaoService->get('site.footer.endereco_linha1', 'Rua da Industria, 1000'),
                'endereco_linha2' => $configuracaoService->get('site.footer.endereco_linha2', 'Distrito Industrial'),
            ],
            'metaTitle' => $configuracaoService->get('site.meta_title', 'Sindimir'),
        ]);
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

    public function contato(ConfiguracaoService $configuracaoService): View
    {
        return view('public.contato', [
            'footer' => [
                'titulo' => $configuracaoService->get('site.footer.titulo', 'Sindimir'),
                'descricao' => $configuracaoService->get(
                    'site.footer.descricao',
                    'Solucoes digitais para capacitacao, eventos e desenvolvimento do setor metal mecanico.'
                ),
                'contato_titulo' => $configuracaoService->get('site.footer.contato_titulo', 'Contato'),
                'contato_email' => $configuracaoService->get('site.footer.contato_email', 'contato@sindimir.org'),
                'contato_telefone' => $configuracaoService->get('site.footer.contato_telefone', '(00) 0000-0000'),
                'endereco_titulo' => $configuracaoService->get('site.footer.endereco_titulo', 'Endereco'),
                'endereco_linha1' => $configuracaoService->get('site.footer.endereco_linha1', 'Rua da Industria, 1000'),
                'endereco_linha2' => $configuracaoService->get('site.footer.endereco_linha2', 'Distrito Industrial'),
            ],
            'metaTitle' => $configuracaoService->get('site.meta_title', 'Sindimir'),
        ]);
    }
}
