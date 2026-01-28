<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContatoMensagemMail;
use App\Services\ConfiguracaoService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContatoController extends Controller
{
    public function index(ConfiguracaoService $configuracaoService): View
    {
        return view('public.contato.index', [
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

    public function enviar(Request $request): RedirectResponse
    {
        $request->merge([
            'telefone' => Phone::normalize($request->input('telefone')),
        ]);
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email',
            'telefone' => 'nullable|string|digits_between:10,11',
            'assunto' => 'required|string|max:255',
            'mensagem' => 'required|string',
        ]);

        $destino = config_db('sistema.email_padrao');

        if (!$destino) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'E-mail institucional nao configurado.']);
        }

        try {
            Mail::to($destino)->send(new ContatoMensagemMail($data));
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', 'Nao foi possivel enviar a mensagem. Tente novamente.');
        }

        return back()->with('success', 'Mensagem enviada com sucesso.');
    }
}
