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

class BotEngine
{
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

        if ($conversation->wasRecentlyCreated || $this->isSessionExpired($conversation)) {
            $response = $this->respondWithMenu($conversation, true);
            $this->logMessage($conversation, 'out', ['text' => $response]);

            return $response;
        }

        if ($this->isResetKeyword($text) || $this->isEntryKeyword($text)) {
            $response = $this->respondWithMenu($conversation, true);
            $this->logMessage($conversation, 'out', ['text' => $response]);

            return $response;
        }

        $state = $this->normalizeState((string) ($conversation->state ?? ''));
        $response = match ($state) {
            BotState::MENU => $this->handleMenuInput($conversation, $text),
            BotState::CURSOS_LIST => $this->handleCoursesInput($conversation, $text),
            BotState::CANCEL_CPF => $this->handleCancelCpfInput($conversation, $text),
            BotState::CANCEL_LIST => $this->handleCancelSelectionInput($conversation, $text),
            BotState::CANCEL_CONFIRM => $this->handleCancelConfirmInput($conversation, $text),
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
                'Nenhum curso disponivel para inscricao no momento.'
            );
        }

        $lines = [];
        foreach ($eventos as $index => $evento) {
            $nomeCurso = $evento->curso?->nome ?? 'Curso';
            $periodo = $this->formatPeriodo($evento);
            $turno = $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Nao informado';
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
            'Cursos disponiveis:',
            ...$lines,
            '',
            'Digite o numero do curso para receber resumo e link de inscricao.',
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
                'Opcao invalida. Escolha um item da lista de cursos.'
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
                'O curso selecionado nao esta mais disponivel.'
            );
        }

        $inscricaoUrl = route('public.cpf', ['evento_curso_id' => $evento->id]);
        $turno = $evento->turno?->value ? ucfirst(str_replace('_', ' ', $evento->turno->value)) : 'Nao informado';

        $this->setConversationState($conversation, BotState::MENU, []);

        return implode("\n", [
            'Resumo do curso:',
            'Curso: ' . $evento->curso->nome,
            'Evento: ' . ($evento->numero_evento ?: '#' . $evento->id),
            'Periodo: ' . $this->formatPeriodo($evento),
            'Horario: ' . $this->formatHorario($evento),
            'Turno: ' . $turno,
            'Municipio: ' . ($evento->municipio ?: 'Nao informado'),
            'Local: ' . ($evento->local_realizacao ?: 'Nao informado'),
            'Inscricao: ' . $inscricaoUrl,
            '',
            'Digite ' . $this->getResetKeyword() . ' para voltar ao menu.',
        ]);
    }

    private function askCancelCpf(BotConversation $conversation): string
    {
        $this->setConversationState($conversation, BotState::CANCEL_CPF, []);

        return 'Informe o CPF com 11 numeros para localizar suas inscricoes.';
    }

    private function handleCancelCpfInput(BotConversation $conversation, string $text): string
    {
        $cpf = Cpf::normalize($text);
        $requireValidCpf = $this->getBoolSetting('bot.cancel.require_valid_cpf', true);

        if ($cpf === '') {
            return 'CPF nao informado. Envie somente os numeros do CPF.';
        }

        if ($requireValidCpf && ! Cpf::isValid($cpf)) {
            return 'CPF invalido. Envie um CPF valido com 11 numeros.';
        }

        $aluno = Aluno::query()->whereCpf($cpf)->first();
        if (! $aluno) {
            return $this->respondWithMenu($conversation, false, 'Nenhuma inscricao encontrada para o CPF informado.');
        }

        $items = $this->buscarItensParaCancelamento($aluno->id);
        if ($items === []) {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Nenhuma inscricao elegivel para cancelamento foi encontrada.'
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
            'Inscricoes encontradas:',
            ...$lines,
            '',
            'Digite o numero da inscricao que deseja cancelar.',
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
                'Opcao invalida. Escolha um item da lista de inscricoes.'
            );
        }

        $selectedItem = $items[$option - 1] ?? null;

        if (! is_array($selectedItem)) {
            return $this->renderCancelListWithPrefix(
                $conversation,
                'A inscricao selecionada nao esta mais disponivel para cancelamento.'
            );
        }

        if (! $this->shouldRequireCancelConfirmation()) {
            return $this->cancelarItemSelecionado($conversation, $selectedItem);
        }

        $context['selected_item'] = $selectedItem;
        $this->setConversationState($conversation, BotState::CANCEL_CONFIRM, $context);

        $typeLabel = ($selectedItem['type'] ?? '') === 'matricula' ? 'Matricula' : 'Inscricao';

        return implode("\n", [
            'Confirmar cancelamento?',
            'Tipo: ' . $typeLabel,
            'Curso: ' . ($selectedItem['curso_nome'] ?? 'Curso'),
            'Periodo: ' . ($selectedItem['periodo'] ?? 'Data nao informada'),
            '1) Sim',
            '2) Nao',
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
            return $this->respondWithMenu($conversation, false, 'Cancelamento nao confirmado.');
        }

        return 'Resposta invalida. Digite 1 para confirmar ou 2 para manter a inscricao.';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function cancelarItemSelecionado(BotConversation $conversation, array $item): string
    {
        $type = (string) ($item['type'] ?? '');
        $id = (int) ($item['id'] ?? 0);

        if ($id <= 0 || ! in_array($type, ['matricula', 'inscricao'], true)) {
            return $this->respondWithMenu($conversation, false, 'A inscricao selecionada nao foi encontrada.');
        }

        if ($type === 'matricula') {
            $matricula = Matricula::query()
                ->with(['eventoCurso.curso', 'aluno'])
                ->find($id);

            if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
                return $this->respondWithMenu($conversation, false, 'A matricula selecionada nao foi encontrada.');
            }

            if ($matricula->status === StatusMatricula::Cancelada) {
                return $this->respondWithMenu($conversation, false, 'Esta matricula ja foi cancelada.');
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

            return $this->respondWithMenu($conversation, false, 'Matricula cancelada com sucesso.');
        }

        $inscricao = ListaEspera::query()
            ->with('eventoCurso.curso')
            ->find($id);

        if (! $inscricao || ! $inscricao->eventoCurso || ! $inscricao->eventoCurso->curso) {
            return $this->respondWithMenu($conversation, false, 'A inscricao selecionada nao foi encontrada.');
        }

        $this->matriculaService->removerDaListaEspera($inscricao);

        return $this->respondWithMenu($conversation, false, 'Inscricao cancelada com sucesso.');
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
            '1) Cursos',
            '2) Cancelamento',
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
            'Inscricoes encontradas:',
            ...$lines,
            '',
            'Digite o numero da inscricao que deseja cancelar.',
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
                . ($item['status'] ?? 'Nao informado')
                . ')'
                . ' - '
                . ($item['periodo'] ?? 'Data nao informada');
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
        $conversation->update([
            'state' => $state,
            'context' => $context,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConversationContext(BotConversation $conversation): array
    {
        $context = $conversation->context;

        return is_array($context) ? $context : [];
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

    private function getResetKeyword(): string
    {
        $value = trim((string) $this->configuracaoService->get('bot.reset_keyword', 'menu'));

        return $value !== '' ? $value : 'menu';
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
            'Nao entendi sua mensagem. Escolha uma opcao valida.'
        ));

        if ($message === '') {
            return 'Nao entendi sua mensagem. Escolha uma opcao valida.';
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
            return 'Data nao informada';
        }

        if (! $evento->data_fim || $evento->data_fim->isSameDay($evento->data_inicio)) {
            return $evento->data_inicio->format('d/m/Y');
        }

        return $evento->data_inicio->format('d/m/Y') . ' a ' . $evento->data_fim->format('d/m/Y');
    }

    private function formatHorario(EventoCurso $evento): string
    {
        if (! $evento->horario_inicio && ! $evento->horario_fim) {
            return 'Nao informado';
        }

        $inicio = $evento->horario_inicio ? substr($evento->horario_inicio, 0, 5) : '--:--';
        $fim = $evento->horario_fim ? substr($evento->horario_fim, 0, 5) : '--:--';

        return $inicio . ' as ' . $fim;
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
