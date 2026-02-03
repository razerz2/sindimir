<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExternalContactsImportService;
use App\Services\GoogleContactsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleContactsController extends Controller
{
    public function __construct(
        private readonly GoogleContactsService $googleContactsService,
        private readonly ExternalContactsImportService $importService
    ) {
    }

    public function connect(): RedirectResponse
    {
        $url = $this->googleContactsService->getAuthUrl();

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->get('code');
        if (! $code) {
            return redirect()
                ->route('admin.configuracoes.index')
                ->with('status', 'Não foi possível autenticar com o Google.');
        }

        try {
            $account = $this->googleContactsService->handleCallback($code);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('admin.configuracoes.index')
                ->with('status', $exception->getMessage());
        }

        $label = $account['email'] ?? 'conta conectada';

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', 'Conta Google conectada: ' . $label . '.');
    }

    public function import(): RedirectResponse
    {
        try {
            $result = $this->importService->importFromGoogle();
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('admin.configuracoes.index')
                ->with('status', $exception->getMessage());
        }

        $message = sprintf(
            'Importação concluída. Importados: %d, duplicados: %d, conflito com alunos: %d.',
            $result['importados'],
            $result['duplicados'],
            $result['conflitos_aluno']
        );

        if ($result['sem_telefone'] > 0) {
            $message .= ' Sem telefone: ' . $result['sem_telefone'] . '.';
        }

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', $message);
    }

    public function disconnect(): RedirectResponse
    {
        $this->googleContactsService->disconnect();

        return redirect()
            ->route('admin.configuracoes.index')
            ->with('status', 'Integração Google removida.');
    }
}
