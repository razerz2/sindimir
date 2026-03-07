<?php

namespace App\Services\Bot;

use App\Enums\LegacyNotificationType;
use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\Aluno;
use App\Models\BotConversation;
use App\Models\BotMessageLog;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Services\ConfiguracaoService;
use App\Services\EventoCursoService;
use App\Services\MatriculaService;
use App\Services\NotificationService;
use App\Support\Cpf;
use App\Support\Phone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BotEngine
{
    private ?bool $hasConversationIsOpenColumn = null;

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly MatriculaService $matriculaService,
        private readonly EventoCursoService $eventoCursoService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function handleIncoming(string $channel, string $from, string $text): string
    {
        $channel = $this->normalizeChannel($channel);
        $from = Phone::normalize($from);
        $text = trim($text);

        if ($from === '') {
            return $this->buildMenuText(true);
        }

        if (! Schema::hasTable('bot_conversations')) {
            return $this->buildMenuText(true);
        }

        $conversation = BotConversation::query()->firstOrCreate(
            [
                'channel' => $channel,
                'from' => $from,
            ],
            [
                'state' => BotState::MENU,
                'context' => [],
                'last_activity_at' => now(),
            ]
        );

        $this->logMessage($conversation, 'in', [
            'channel' => $channel,
            'from' => $from,
            'text' => $text,
        ]);

        if ($this->isResetKeyword($text) || $this->isEntryKeyword($text)) {
            $response = $this->respondWithMenu($conversation, true);
            $this->logMessage($conversation, 'out', ['text' => $response]);

            return $response;
        }

        if ($this->isExitKeyword($text)) {
            $response = $this->closeConversation($conversation);
            $this->logMessage($conversation, 'out', ['text' => $response]);

            return $response;
        }

        if ($conversation->wasRecentlyCreated || $this->isSessionExpired($conversation)) {
            $response = $this->respondWithMenu($conversation, true);
            $this->logMessage($conversation, 'out', ['text' => $response]);

            return $response;
        }

        $state = $this->normalizeState((string) ($conversation->state ?? ''));
        $response = match ($state) {
            BotState::MENU => $this->handleMenuInput($conversation, $text),
            BotState::CURSOS_LIST => $this->handleCoursesInput($conversation, $text),
            BotState::CURSO_ACTION => $this->handleCourseActionInput($conversation, $text),
            BotState::CURSO_CPF => $this->handleCourseCpfInput($conversation, $text),
            BotState::CANCEL_CPF => $this->handleCancelCpfInput($conversation, $text),
            BotState::CANCEL_LIST => $this->handleCancelSelectionInput($conversation, $text),
            BotState::CANCEL_CONFIRM => $this->handleCancelConfirmInput($conversation, $text),
            BotState::ENDED => $this->getCloseMessage(),
            default => $this->respondWithMenu($conversation, true),
        };

        $this->logMessage($conversation, 'out', ['text' => $response]);

        return $response;
    }

    private function handleMenuInput(BotConversation $conversation, string $text): string
    {
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            return $this->listCourses($conversation);
        }

        if ($option === 2) {
            return $this->askCancelCpf($conversation);
        }

        return $this->respondWithMenu($conversation, false, $this->getFallbackMessage());
    }

    private function listCourses(BotConversation $conversation): string
    {
        $order = $this->getCoursesOrder();
        $limit = $this->getCoursesLimit();

        $eventos = EventoCurso::query()
            ->with('curso')
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->orderBy('data_inicio', $order)
            ->orderBy('id', $order)
            ->limit($limit)
            ->get();

        if ($eventos->isEmpty()) {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Nenhum curso disponível para inscrição no momento.'
            );
        }

        $lines = [];
        foreach ($eventos as $index => $evento) {
            $nomeCurso = $evento->curso?->nome ?? 'Curso';
            $periodo = $this->formatPeriodo($evento);
            $turno = $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Não informado';
            $resumo = $this->eventoCursoService->resumoVagas($evento);
            $vagas = $resumo['total_vagas'] > 0
                ? ' - Vagas: ' . $resumo['vagas_disponiveis'] . '/' . $resumo['total_vagas']
                : '';
            $lines[] = ($index + 1) . ') ' . $nomeCurso . ' - ' . $periodo . ' - ' . $turno . $vagas;
        }

        $this->setConversationState(
            $conversation,
            BotState::CURSOS_LIST,
            ['course_event_ids' => $eventos->pluck('id')->all()]
        );

        return implode("\n", [
            'Cursos disponíveis:',
            ...$lines,
            '',
            'Digite o número do curso para receber resumo e link de inscrição.',
        ]);
    }

    private function handleCoursesInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $eventIds = $context['course_event_ids'] ?? [];

        if (! is_array($eventIds) || $eventIds === []) {
            return $this->listCourses($conversation);
        }

        $option = $this->parseNumericOption($text);
        if ($option === null || $option < 1 || $option > count($eventIds)) {
            return $this->listCoursesWithPrefix(
                $conversation,
                'Opção inválida. Escolha um item da lista de cursos.'
            );
        }

        $selectedEventId = (int) $eventIds[$option - 1];
        $evento = EventoCurso::query()
            ->with('curso')
            ->where('id', $selectedEventId)
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->first();

        if (! $evento || ! $evento->curso) {
            return $this->listCoursesWithPrefix(
                $conversation,
                'O curso selecionado não está mais disponível.'
            );
        }

        $turno = $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Não informado';
        $context['selected_evento_id'] = $evento->id;
        $context['selected_index'] = $option;
        $this->setConversationState($conversation, BotState::CURSO_ACTION, $context);

        return implode("\n", [
            'Resumo do curso:',
            'Curso: ' . $evento->curso->nome,
            'Evento: ' . ($evento->numero_evento ?: '#' . $evento->id),
            'Período: ' . $this->formatPeriodo($evento),
            'Horário: ' . $this->formatHorario($evento),
            'Turno: ' . $turno,
            'Município: ' . ($evento->municipio ?: 'Não informado'),
            'Local: ' . ($evento->local_realizacao ?: 'Não informado'),
            '',
            'O que você deseja fazer?',
            '1) Inscrever pelo WhatsApp (CPF)',
            '2) Receber link do site',
            'Responda com 1 ou 2.',
        ]);
    }

    private function handleCourseActionInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $evento = $this->getSelectedEventoFromContext($context);

        if (! $evento || ! $evento->curso) {
            return $this->respondWithMenu($conversation, false, 'Curso selecionado não encontrado.');
        }

        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            $this->setConversationState($conversation, BotState::CURSO_CPF, $context);

            return 'Informe seu CPF (somente números).';
        }

        if ($option === 2) {
            $this->setConversationState($conversation, BotState::MENU, []);

            return implode("\n", [
                'Acesse o link para se inscrever:',
                $this->buildInscricaoLink($evento->id),
                '',
                'Digite menu para voltar ao início.',
            ]);
        }

        return implode("\n", [
            'Opção inválida.',
            '1) Inscrever pelo WhatsApp (CPF)',
            '2) Receber link do site',
            'Responda com 1 ou 2.',
        ]);
    }

    private function handleCourseCpfInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $evento = $this->getSelectedEventoFromContext($context);

        if (! $evento || ! $evento->curso) {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível localizar o curso selecionado. Tente novamente.'
            );
        }

        $cpf = Cpf::normalize($text);
        if ($cpf === '') {
            return 'CPF não informado. Envie apenas números.';
        }

        if (! Cpf::isValid($cpf)) {
            return 'CPF inválido. Envie apenas números.';
        }

        $aluno = $this->findAlunoByCpf($cpf);
        $link = $this->buildInscricaoLink($evento->id);

        if (! $aluno) {
            return $this->respondWithMenu(
                $conversation,
                false,
                "Não encontrei seu cadastro. Para se inscrever, conclua no site:\n{$link}"
            );
        }

        $matriculaAtiva = Matricula::query()
            ->where('aluno_id', $aluno->id)
            ->where('evento_curso_id', $evento->id)
            ->whereIn('status', [StatusMatricula::Pendente->value, StatusMatricula::Confirmada->value])
            ->exists();

        if ($matriculaAtiva) {
            return $this->respondWithMenu(
                $conversation,
                false,
                "Você já possui inscrição neste curso. Se precisar, acesse:\n{$link}"
            );
        }

        try {
            $resultado = $this->matriculaService->solicitarInscricao($aluno->id, $evento->id);
        } catch (Throwable) {
            return $this->respondWithMenu(
                $conversation,
                false,
                "Não foi possível concluir sua inscrição agora. Tente pelo site:\n{$link}"
            );
        }

        if (($resultado['tipo'] ?? null) === 'lista_espera') {
            return $this->respondWithMenu(
                $conversation,
                false,
                implode("\n", [
                    'No momento não há vagas imediatas.',
                    'Você foi incluído na lista de espera com sucesso.',
                    'Curso: ' . $evento->curso->nome,
                    'Período: ' . $this->formatPeriodo($evento),
                    'Local: ' . ($evento->local_realizacao ?: 'Não informado'),
                ])
            );
        }

        $registro = $resultado['registro'] ?? null;
        if (! $registro instanceof Matricula) {
            return $this->respondWithMenu(
                $conversation,
                false,
                "Não foi possível concluir sua inscrição agora. Use o link:\n{$link}"
            );
        }

        if (! in_array($registro->status, [StatusMatricula::Pendente, StatusMatricula::Confirmada], true)) {
            return $this->respondWithMenu(
                $conversation,
                false,
                "Não foi possível concluir sua inscrição com este CPF. Use o link:\n{$link}"
            );
        }

        return $this->respondWithMenu(
            $conversation,
            false,
            implode("\n", [
                'Inscrição realizada com sucesso.',
                'Curso: ' . $evento->curso->nome,
                'Período: ' . $this->formatPeriodo($evento),
                'Local: ' . ($evento->local_realizacao ?: 'Não informado'),
            ])
        );
    }

    private function askCancelCpf(BotConversation $conversation): string
    {
        $this->setConversationState($conversation, BotState::CANCEL_CPF, []);

        return 'Informe o CPF com 11 números para localizar suas inscrições.';
    }

    private function handleCancelCpfInput(BotConversation $conversation, string $text): string
    {
        $cpf = Cpf::normalize($text);
        $requireValidCpf = $this->getBoolSetting('bot.cancel.require_valid_cpf', true);

        if ($cpf === '') {
            return 'CPF não informado. Envie apenas números.';
        }

        if ($requireValidCpf && ! Cpf::isValid($cpf)) {
            return 'CPF inválido. Envie apenas números.';
        }

        $aluno = Aluno::query()->whereCpf($cpf)->first();
        if (! $aluno) {
            return $this->respondWithMenu($conversation, false, 'Nenhuma inscrição encontrada para o CPF informado.');
        }

        $items = $this->buscarItensParaCancelamento($aluno->id);
        if ($items === []) {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Nenhuma inscrição elegível para cancelamento foi encontrada.'
            );
        }

        $lines = $this->buildCancelListLines($items);

        $this->setConversationState(
            $conversation,
            BotState::CANCEL_LIST,
            [
                'cancel_cpf' => $cpf,
                'cancel_items' => $items,
            ]
        );

        return implode("\n", [
            'Inscrições encontradas:',
            ...$lines,
            '',
            'Digite o número da inscrição que deseja cancelar.',
        ]);
    }

    private function handleCancelSelectionInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $items = $context['cancel_items'] ?? [];

        if (! is_array($items) || $items === []) {
            return $this->askCancelCpf($conversation);
        }

        $option = $this->parseNumericOption($text);
        if ($option === null || $option < 1 || $option > count($items)) {
            return $this->renderCancelListWithPrefix(
                $conversation,
                'Opção inválida. Escolha um item da lista de inscrições.'
            );
        }

        $selectedItem = $items[$option - 1] ?? null;

        if (! is_array($selectedItem)) {
            return $this->renderCancelListWithPrefix(
                $conversation,
                'A inscrição selecionada não está mais disponível para cancelamento.'
            );
        }

        if (! $this->shouldRequireCancelConfirmation()) {
            return $this->cancelarItemSelecionado($conversation, $selectedItem);
        }

        $context['selected_item'] = $selectedItem;
        $this->setConversationState($conversation, BotState::CANCEL_CONFIRM, $context);

        $typeLabel = ($selectedItem['type'] ?? '') === 'matricula' ? 'Matrícula' : 'Inscrição';

        return implode("\n", [
            'Confirmar cancelamento?',
            'Tipo: ' . $typeLabel,
            'Curso: ' . ($selectedItem['curso_nome'] ?? 'Curso'),
            'Período: ' . ($selectedItem['periodo'] ?? 'Data não informada'),
            '1) Sim',
            '2) Não',
            'Responda com 1 ou 2.',
        ]);
    }

    private function handleCancelConfirmInput(BotConversation $conversation, string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $context = $this->getConversationContext($conversation);
        $selectedItem = $context['selected_item'] ?? null;

        if (! is_array($selectedItem)) {
            return $this->askCancelCpf($conversation);
        }

        if (in_array($normalized, ['1', 'sim', 's'], true)) {
            return $this->cancelarItemSelecionado($conversation, $selectedItem);
        }

        if (in_array($normalized, ['2', 'nao', 'não', 'n'], true)) {
            return $this->respondWithMenu($conversation, false, 'Cancelamento não confirmado.');
        }

        return 'Resposta inválida. Digite 1 para confirmar ou 2 para manter a inscrição.';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function cancelarItemSelecionado(BotConversation $conversation, array $item): string
    {
        $type = (string) ($item['type'] ?? '');
        $id = (int) ($item['id'] ?? 0);

        if ($id <= 0 || ! in_array($type, ['matricula', 'inscricao'], true)) {
            return $this->respondWithMenu($conversation, false, 'A inscrição selecionada não foi encontrada.');
        }

        if ($type === 'matricula') {
            $matricula = Matricula::query()
                ->with(['eventoCurso.curso', 'aluno'])
                ->find($id);

            if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
                return $this->respondWithMenu($conversation, false, 'A matrícula selecionada não foi encontrada.');
            }

            if ($matricula->status === StatusMatricula::Cancelada) {
                return $this->respondWithMenu($conversation, false, 'Esta matrícula já foi cancelada.');
            }

            $this->matriculaService->cancelarMatricula($matricula);
            $matricula->loadMissing(['eventoCurso.curso', 'aluno']);

            if ($matricula->aluno) {
                $this->notificationService->disparar(
                    [$matricula->aluno],
                    $matricula->eventoCurso,
                    LegacyNotificationType::INSCRICAO_CANCELADA
                );
            }

            return $this->respondWithMenu($conversation, false, 'Matrícula cancelada com sucesso.');
        }

        $inscricao = ListaEspera::query()
            ->with('eventoCurso.curso')
            ->find($id);

        if (! $inscricao || ! $inscricao->eventoCurso || ! $inscricao->eventoCurso->curso) {
            return $this->respondWithMenu($conversation, false, 'A inscrição selecionada não foi encontrada.');
        }

        $this->matriculaService->removerDaListaEspera($inscricao);

        return $this->respondWithMenu($conversation, false, 'Inscrição cancelada com sucesso.');
    }

    private function respondWithMenu(BotConversation $conversation, bool $includeWelcome, ?string $prefix = null): string
    {
        $this->setConversationState($conversation, BotState::MENU, []);

        $parts = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $parts[] = trim($prefix);
        }

        if ($includeWelcome) {
            $parts[] = $this->getWelcomeMessage();
        }

        $parts[] = $this->buildMenuOnlyText();

        return implode("\n\n", $parts);
    }

    private function buildMenuText(bool $includeWelcome): string
    {
        if (! $includeWelcome) {
            return $this->buildMenuOnlyText();
        }

        return $this->getWelcomeMessage() . "\n\n" . $this->buildMenuOnlyText();
    }

    private function buildMenuOnlyText(): string
    {
        return implode("\n", [
            '1) Cursos Disponíveis',
            '2) Cancelar Inscrição',
            '',
            'Responda com 1 ou 2.',
        ]);
    }

    private function listCoursesWithPrefix(BotConversation $conversation, string $prefix): string
    {
        $listText = $this->listCourses($conversation);

        return $prefix . "\n\n" . $listText;
    }

    private function renderCancelListWithPrefix(BotConversation $conversation, string $prefix): string
    {
        $context = $this->getConversationContext($conversation);
        $items = $context['cancel_items'] ?? [];

        if (! is_array($items) || $items === []) {
            return $this->askCancelCpf($conversation);
        }
        $lines = $this->buildCancelListLines($items);

        return implode("\n", [
            $prefix,
            '',
            'Inscrições encontradas:',
            ...$lines,
            '',
            'Digite o número da inscrição que deseja cancelar.',
        ]);
    }

    /**
     * @return list<array{type: string, id: int, curso_id: int, curso_nome: string, periodo: string, status: string}>
     */
    private function buscarItensParaCancelamento(int $alunoId): array
    {
        $limit = $this->getCancelLimit();
        $order = $this->getCancelOrder();
        $onlyActiveEvents = $this->getBoolSetting('bot.cancel.only_active_events', true);

        $matriculasQuery = Matricula::query()
            ->with(['eventoCurso.curso'])
            ->where('aluno_id', $alunoId)
            ->whereIn('status', [StatusMatricula::Pendente->value, StatusMatricula::Confirmada->value]);

        if ($onlyActiveEvents) {
            $matriculasQuery->whereHas('eventoCurso', function ($eventoQuery) {
                $eventoQuery
                    ->where('ativo', true)
                    ->whereHas('curso', fn ($cursoQuery) => $cursoQuery->where('ativo', true));
            });
        }

        /** @var Collection<int, Matricula> $matriculas */
        $matriculas = $matriculasQuery
            ->orderBy('created_at', $order)
            ->limit($limit)
            ->get();

        $inscricoesQuery = ListaEspera::query()
            ->with(['eventoCurso.curso'])
            ->where('aluno_id', $alunoId)
            ->whereIn('status', [StatusListaEspera::Aguardando->value, StatusListaEspera::Chamado->value]);

        if ($onlyActiveEvents) {
            $inscricoesQuery->whereHas('eventoCurso', function ($eventoQuery) {
                $eventoQuery
                    ->where('ativo', true)
                    ->whereHas('curso', fn ($cursoQuery) => $cursoQuery->where('ativo', true));
            });
        }

        /** @var Collection<int, ListaEspera> $inscricoes */
        $inscricoes = $inscricoesQuery
            ->orderBy('created_at', $order)
            ->limit($limit)
            ->get();

        $byCourse = [];

        foreach ($matriculas as $matricula) {
            $evento = $matricula->eventoCurso;
            $curso = $evento?->curso;
            if (! $evento || ! $curso) {
                continue;
            }

            $courseId = (int) $curso->id;
            $byCourse[$courseId] = [
                'type' => 'matricula',
                'id' => (int) $matricula->id,
                'curso_id' => $courseId,
                'curso_nome' => (string) $curso->nome,
                'periodo' => $this->formatPeriodo($evento),
                'status' => ucfirst((string) ($matricula->status->value ?? $matricula->status)),
            ];
        }

        foreach ($inscricoes as $inscricao) {
            $evento = $inscricao->eventoCurso;
            $curso = $evento?->curso;
            if (! $evento || ! $curso) {
                continue;
            }

            $courseId = (int) $curso->id;
            if (isset($byCourse[$courseId])) {
                // Regra: se houver ambos para o mesmo curso, prioriza matricula.
                continue;
            }

            $byCourse[$courseId] = [
                'type' => 'inscricao',
                'id' => (int) $inscricao->id,
                'curso_id' => $courseId,
                'curso_nome' => (string) $curso->nome,
                'periodo' => $this->formatPeriodo($evento),
                'status' => ucfirst((string) ($inscricao->status->value ?? $inscricao->status)),
            ];
        }

        $items = array_values($byCourse);

        usort($items, static function (array $a, array $b): int {
            return strcmp($a['curso_nome'], $b['curso_nome']);
        });

        if ($order === 'desc') {
            $items = array_reverse($items);
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * @param list<array{type: string, id: int, curso_id: int, curso_nome: string, periodo: string, status: string}> $items
     * @return list<string>
     */
    private function buildCancelListLines(array $items): array
    {
        $lines = [];

        foreach (array_values($items) as $index => $item) {
            $typeLabel = ($item['type'] ?? '') === 'matricula' ? 'Matrícula' : 'Inscrição';
            $lines[] = ($index + 1) . ') '
                . ($item['curso_nome'] ?? 'Curso')
                . ' — '
                . $typeLabel
                . ' ('
                . ($item['status'] ?? 'Não informado')
                . ')'
                . ' - '
                . ($item['periodo'] ?? 'Data não informada');
        }

        return $lines;
    }

    private function normalizeState(string $state): string
    {
        if (! in_array($state, BotState::all(), true)) {
            return BotState::MENU;
        }

        return $state;
    }

    private function setConversationState(BotConversation $conversation, string $state, array $context): void
    {
        $payload = [
            'state' => $state,
            'context' => $context,
            'last_activity_at' => now(),
        ];

        if ($this->hasConversationIsOpenColumn()) {
            $payload['is_open'] = $state !== BotState::ENDED;
        }

        $conversation->update($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConversationContext(BotConversation $conversation): array
    {
        $context = $conversation->context;

        return is_array($context) ? $context : [];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getSelectedEventoFromContext(array $context): ?EventoCurso
    {
        $eventoId = (int) ($context['selected_evento_id'] ?? 0);
        if ($eventoId <= 0) {
            return null;
        }

        return EventoCurso::query()
            ->with('curso')
            ->where('id', $eventoId)
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->first();
    }

    private function buildInscricaoLink(int $eventoId): string
    {
        return route('public.cpf', ['evento_curso_id' => $eventoId]);
    }

    private function findAlunoByCpf(string $cpf): ?Aluno
    {
        return Aluno::query()
            ->whereCpf($cpf)
            ->first();
    }

    private function isSessionExpired(BotConversation $conversation): bool
    {
        if (! $conversation->last_activity_at) {
            return false;
        }

        $timeoutMinutes = $this->getIntSetting('bot.session_timeout_minutes', 15, 1, 1440);

        return $conversation->last_activity_at->diffInMinutes(now()) >= $timeoutMinutes;
    }

    private function isEntryKeyword(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        $normalized = mb_strtolower(trim($text));

        return in_array($normalized, $this->getEntryKeywords(), true);
    }

    private function isExitKeyword(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        $normalized = $this->normalizeKeywordForMatch($text);
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, $this->getExitKeywords(), true);
    }

    private function isResetKeyword(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        return mb_strtolower(trim($text)) === mb_strtolower($this->getResetKeyword());
    }

    /**
     * @return list<string>
     */
    private function getEntryKeywords(): array
    {
        $raw = $this->configuracaoService->get('bot.entry_keywords', ['oi', 'ola']);
        $keywords = [];

        if (is_array($raw)) {
            $keywords = $raw;
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed !== '' && str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $keywords = $decoded;
                }
            }

            if ($keywords === []) {
                $keywords = preg_split('/[\r\n,;]+/', $raw) ?: [];
            }
        }

        $normalized = [];
        foreach ($keywords as $keyword) {
            $value = mb_strtolower(trim((string) $keyword));
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        if ($normalized === []) {
            return ['oi', 'ola'];
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function getExitKeywords(): array
    {
        $raw = $this->configuracaoService->get('bot.exit_keywords', ['sair', 'tchau', 'encerrar']);
        $keywords = [];

        if (is_array($raw)) {
            $keywords = $raw;
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed !== '' && str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $keywords = $decoded;
                }
            }

            if ($keywords === []) {
                $keywords = preg_split('/[\r\n,;]+/', $raw) ?: [];
            }
        }

        $normalized = [];
        foreach ($keywords as $keyword) {
            $value = $this->normalizeKeywordForMatch((string) $keyword);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        if ($normalized === []) {
            return ['sair', 'tchau', 'encerrar'];
        }

        return array_values(array_unique($normalized));
    }

    private function getResetKeyword(): string
    {
        $value = trim((string) $this->configuracaoService->get('bot.reset_keyword', 'menu'));

        return $value !== '' ? $value : 'menu';
    }

    private function getCloseMessage(): string
    {
        $message = trim((string) $this->configuracaoService->get(
            'bot.close_message',
            'Atendimento encerrado. Quando precisar, digite *menu* para comecar novamente.'
        ));

        if ($message === '') {
            return 'Atendimento encerrado. Quando precisar, digite *menu* para comecar novamente.';
        }

        return $message;
    }

    private function getWelcomeMessage(): string
    {
        $message = trim((string) $this->configuracaoService->get(
            'bot.welcome_message',
            'Bem-vindo ao bot do Sindimir. Escolha uma opcao:'
        ));

        if ($message === '') {
            return 'Bem-vindo ao bot do Sindimir. Escolha uma opcao:';
        }

        return $message;
    }

    private function getFallbackMessage(): string
    {
        $message = trim((string) $this->configuracaoService->get(
            'bot.fallback_message',
            'Não entendi sua mensagem. Escolha uma opção válida.'
        ));

        if ($message === '') {
            return 'Não entendi sua mensagem. Escolha uma opção válida.';
        }

        return $message;
    }

    private function getCoursesLimit(): int
    {
        return $this->getIntSetting('bot.courses.limit', 10, 1, 50);
    }

    private function getCoursesOrder(): string
    {
        $order = mb_strtolower((string) $this->configuracaoService->get('bot.courses.order', 'asc'));

        return in_array($order, ['asc', 'desc'], true) ? $order : 'asc';
    }

    private function getCancelLimit(): int
    {
        return $this->getIntSetting('bot.cancel.limit', 10, 1, 50);
    }

    private function getCancelOrder(): string
    {
        $order = mb_strtolower((string) $this->configuracaoService->get('bot.cancel.order', 'desc'));

        return in_array($order, ['asc', 'desc'], true) ? $order : 'desc';
    }

    private function shouldRequireCancelConfirmation(): bool
    {
        $value = $this->configuracaoService->get('bot.cancel.require_confirm', null);
        if ($value === null) {
            $value = $this->configuracaoService->get('bot.cancel.require_confirmation', true);
        }

        return (bool) $value;
    }

    private function getIntSetting(string $key, int $default, int $min, int $max): int
    {
        $value = (int) $this->configuracaoService->get($key, $default);
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    private function getBoolSetting(string $key, bool $default): bool
    {
        return (bool) $this->configuracaoService->get($key, $default);
    }

    private function closeConversation(BotConversation $conversation): string
    {
        $this->setConversationState($conversation, BotState::ENDED, []);

        return $this->getCloseMessage();
    }

    private function normalizeKeywordForMatch(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function hasConversationIsOpenColumn(): bool
    {
        if ($this->hasConversationIsOpenColumn !== null) {
            return $this->hasConversationIsOpenColumn;
        }

        $this->hasConversationIsOpenColumn = Schema::hasColumn('bot_conversations', 'is_open');

        return $this->hasConversationIsOpenColumn;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = mb_strtolower(trim($channel));

        return in_array($channel, ['meta', 'zapi'], true) ? $channel : 'meta';
    }

    private function parseNumericOption(string $text): ?int
    {
        $normalized = trim($text);

        if ($normalized === '' || preg_match('/^\d+$/', $normalized) !== 1) {
            return null;
        }

        return (int) $normalized;
    }

    private function formatPeriodo(EventoCurso $evento): string
    {
        if (! $evento->data_inicio) {
            return 'Data não informada';
        }

        if (! $evento->data_fim || $evento->data_fim->isSameDay($evento->data_inicio)) {
            return $evento->data_inicio->format('d/m/Y');
        }

        return $evento->data_inicio->format('d/m/Y') . ' a ' . $evento->data_fim->format('d/m/Y');
    }

    private function formatHorario(EventoCurso $evento): string
    {
        if (! $evento->horario_inicio && ! $evento->horario_fim) {
            return 'Não informado';
        }

        $inicio = $evento->horario_inicio ? substr($evento->horario_inicio, 0, 5) : '--:--';
        $fim = $evento->horario_fim ? substr($evento->horario_fim, 0, 5) : '--:--';

        return $inicio . ' às ' . $fim;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function logMessage(BotConversation $conversation, string $direction, array $payload): void
    {
        $auditEnabled = (bool) $this->configuracaoService->get('bot.audit_log_enabled', true);
        if (! $auditEnabled) {
            return;
        }

        if (! Schema::hasTable('bot_message_logs')) {
            return;
        }

        if (! in_array($direction, ['in', 'out'], true)) {
            return;
        }

        BotMessageLog::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => $direction,
            'payload' => $payload,
        ]);
    }
}
