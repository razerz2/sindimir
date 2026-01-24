<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusMatricula;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationDispatchRequest;
use App\Http\Requests\Admin\NotificationPreviewRequest;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Services\ConfiguracaoService;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ConfiguracaoService $configuracaoService
    ) {
    }

    public function store(NotificationDispatchRequest $request): JsonResponse
    {
        $destino = $this->resolveDestino($request);
        $alunos = $this->resolveAlunos($request, $destino);

        if ($alunos->isEmpty()) {
            return response()->json(['message' => 'Nenhum aluno válido encontrado.'], 422);
        }

        $this->notificationService->disparar($alunos, $destino);

        return response()->json(['message' => 'Notificações enfileiradas com sucesso.']);
    }

    public function preview(NotificationPreviewRequest $request): JsonResponse
    {
        $aluno = Aluno::query()->findOrFail($request->input('aluno_id'));
        $curso = Curso::query()->findOrFail($request->input('curso_id'));
        $tipo = NotificationType::from($request->input('notification_type'));

        $preview = $this->notificationService->previewTemplate($aluno, $curso, $tipo);

        return response()->json($preview);
    }

    public function index(): View
    {
        $cursos = Curso::query()->orderBy('nome')->get(['id', 'nome']);
        $eventos = EventoCurso::query()
            ->orderBy('data_inicio')
            ->get(['id', 'curso_id', 'numero_evento', 'data_inicio']);
        $alunos = Aluno::query()
            ->orderBy('nome_completo')
            ->get(['id', 'nome_completo']);
        $notificationTypes = NotificationType::cases();
        $settings = [
            'email' => (bool) $this->configuracaoService->get('notificacao.email_ativo', true),
            'whatsapp' => (bool) $this->configuracaoService->get('notificacao.whatsapp_ativo', false),
        ];

        return view('admin.notificacoes.index', compact(
            'cursos',
            'eventos',
            'alunos',
            'notificationTypes',
            'settings'
        ));
    }

    private function resolveDestino(NotificationDispatchRequest $request): Curso|EventoCurso
    {
        if ($request->filled('evento_curso_id')) {
            return EventoCurso::query()->findOrFail($request->input('evento_curso_id'));
        }

        return Curso::query()->findOrFail($request->input('curso_id'));
    }

    private function resolveAlunos(NotificationDispatchRequest $request, Curso|EventoCurso $destino): Collection
    {
        $ids = $request->input('aluno_ids', []);

        if (! empty($ids)) {
            return Aluno::query()->whereIn('id', $ids)->get();
        }

        if ($destino instanceof EventoCurso) {
            return $destino->matriculas()
                ->where('status', StatusMatricula::Confirmada)
                ->with('aluno')
                ->get()
                ->pluck('aluno')
                ->filter(fn (?Aluno $aluno) => $aluno !== null)
                ->values();
        }

        return collect();
    }
}
