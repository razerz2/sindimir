<?php

namespace App\Services\Bot;

use App\Enums\LegacyNotificationType;
use App\Enums\Sexo;
use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use App\Models\Aluno;
use App\Models\BotConversation;
use App\Models\BotMessageLog;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Services\AlunoService;
use App\Services\Bot\Providers\BotProviderFactory;
use App\Services\ConfiguracaoService;
use App\Services\EventoCursoService;
use App\Services\MatriculaService;
use App\Services\NotificationService;
use App\Support\Cpf;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BotEngine
{
    private ?bool $hasConversationIsOpenColumn = null;

    private ?bool $hasConversationClosedAtColumn = null;

    private ?bool $hasConversationClosedReasonColumn = null;

    public function __construct(
        private readonly ConfiguracaoService $configuracaoService,
        private readonly AlunoService $alunoService,
        private readonly MatriculaService $matriculaService,
        private readonly EventoCursoService $eventoCursoService,
        private readonly NotificationService $notificationService,
        private readonly BotProviderFactory $providerFactory
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

        // Any new message to an ENDED conversation reopens it so the user can start a fresh session.
        if (! $conversation->wasRecentlyCreated && (string) ($conversation->state ?? '') === BotState::ENDED) {
            $this->reopenConversation($conversation);
        }

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
            BotState::CURSO_ALUNO_CONFIRM => $this->handleCourseAlunoConfirmInput($conversation, $text),
            BotState::CURSO_ALUNO_EDIT_FIELD => $this->handleCourseAlunoEditFieldInput($conversation, $text),
            BotState::CURSO_ALUNO_EDIT_REVIEW => $this->handleCourseAlunoEditReviewInput($conversation, $text),
            BotState::ALUNO_CPF => $this->handleAlunoCpfInput($conversation, $text),
            BotState::ALUNO_MENU => $this->handleAlunoMenuInput($conversation, $text),
            BotState::ALUNO_VIEW_DATA => $this->handleAlunoViewDataInput($conversation, $text),
            BotState::ALUNO_EDIT_FIELD => $this->handleAlunoEditFieldInput($conversation, $text),
            BotState::ALUNO_EDIT_REVIEW => $this->handleAlunoEditReviewInput($conversation, $text),
            BotState::ALUNO_INSCRICOES_LIST => $this->handleAlunoInscricoesListInput($conversation, $text),
            BotState::ALUNO_INSCRICAO_ACTION => $this->handleAlunoInscricaoActionInput($conversation, $text),
            BotState::TARGET_PICK => $this->handleTargetPickInput($conversation, $text),
            BotState::TARGET_OTHER_CPF => $this->handleTargetOtherCpfInput($conversation, $text),
            BotState::TARGET_OTHER_NOT_FOUND => $this->handleTargetOtherNotFoundInput($conversation, $text),
            BotState::CANCEL_CPF => $this->handleCancelCpfInput($conversation, $text),
            BotState::CANCEL_LIST => $this->handleCancelSelectionInput($conversation, $text),
            BotState::CANCEL_CONFIRM => $this->handleCancelConfirmInput($conversation, $text),
            BotState::ENDED => $this->getCloseMessage(),
            default => $this->respondWithMenu($conversation, true),
        };

        $this->logMessage($conversation, 'out', ['text' => $response]);

        return $response;
    }

    /**
     * Stores the verified reply JID in context so background commands (e.g. bot:close-inactive)
     * can send messages to the correct destination when `from` holds an internal/LID identifier.
     */
    public function persistReplyChatId(string $channel, string $from, string $replyChatId): void
    {
        $channel = $this->normalizeChannel($channel);
        $from = Phone::normalize($from);
        $replyChatId = trim($replyChatId);

        if ($from === '' || $replyChatId === '' || ! Schema::hasTable('bot_conversations')) {
            return;
        }

        $conversation = BotConversation::query()
            ->where('channel', $channel)
            ->where('from', $from)
            ->first();

        if ($conversation === null) {
            return;
        }

        $context = $this->getConversationContext($conversation);
        if (($context['reply_chat_id'] ?? '') === $replyChatId) {
            return;
        }

        $context['reply_chat_id'] = $replyChatId;
        $this->logConversationContextAudit(
            $conversation,
            'bot_engine.persist_reply_chat_id',
            (string) ($conversation->state ?? ''),
            $conversation->closed_reason !== null ? (string) $conversation->closed_reason : null,
            $conversation->context,
            $context
        );
        $conversation->updateWithContextPolicy(['context' => $context], false, __METHOD__);
    }

    private function handleMenuInput(BotConversation $conversation, string $text): string
    {
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            return $this->listCourses($conversation);
        }

        if ($option === 2) {
            return $this->askAlunoCpf($conversation);
        }

        if ($option === 3) {
            return $this->askCancelCpf($conversation);
        }

        return $this->respondWithMenu($conversation, false, $this->getFallbackMessage());
    }

    private function listCourses(BotConversation $conversation): string
    {
        $order = $this->getCoursesOrder();
        $limit = $this->getCoursesLimit();

        $eventosQuery = EventoCurso::query()
            ->with('curso')
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true))
            ->orderBy('data_inicio', $order);

        $this->applyActiveCourseDateFilter($eventosQuery);

        $eventos = $eventosQuery
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
            $horario = (! $evento->horario_inicio && ! $evento->horario_fim)
                ? 'Horário não informado'
                : 'Horário: ' . $this->formatHorario($evento);
            $resumo = $this->eventoCursoService->resumoVagas($evento);
            $vagas = $resumo['total_vagas'] > 0
                ? ' - Vagas: ' . $resumo['vagas_disponiveis'] . '/' . $resumo['total_vagas']
                : '';
            $lines[] = $this->toEmojiNumber($index + 1)
                . ' '
                . $nomeCurso
                . ' - '
                . $periodo
                . ' - '
                . $horario
                . $vagas;
        }

        $this->setConversationState(
            $conversation,
            BotState::CURSOS_LIST,
            ['course_event_ids' => $eventos->pluck('id')->all()]
        );

        return implode("\n", [
            'Cursos disponíveis:',
            ...$lines,
            '0️⃣ Voltar',
            '',
            'Digite o número do curso para ver o resumo e prosseguir com a inscrição.',
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
        if ($option === 0) {
            return $this->respondWithMenu($conversation, false);
        }

        if ($option === null || $option < 1 || $option > count($eventIds)) {
            return $this->listCoursesWithPrefix(
                $conversation,
                'Opção inválida. Escolha um item da lista de cursos.'
            );
        }

        $selectedEventId = (int) $eventIds[$option - 1];
        $eventoQuery = EventoCurso::query()
            ->with('curso')
            ->where('id', $selectedEventId)
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true));

        $this->applyActiveCourseDateFilter($eventoQuery);

        $evento = $eventoQuery->first();

        if (! $evento || ! $evento->curso) {
            return $this->listCoursesWithPrefix(
                $conversation,
                'O curso selecionado não está mais disponível.'
            );
        }

        $context['selected_evento_id'] = $evento->id;
        $context['selected_event_id'] = $evento->id;
        $context['selected_index'] = $option;
        $this->setConversationState($conversation, BotState::CURSO_ACTION, $context);

        return $this->buildOptionsMessage(
            [
                'Resumo do curso:',
                'Curso: ' . $evento->curso->nome,
                'Evento: ' . ($evento->numero_evento ?: '#' . $evento->id),
                'Período: ' . $this->formatPeriodo($evento),
                'Horário: ' . $this->formatHorario($evento),
                'Município: ' . ($evento->municipio ?: 'Não informado'),
                'Local: ' . ($evento->local_realizacao ?: 'Não informado'),
            ],
            [
                '1) Inscrever-se',
                '2) Voltar',
            ],
            'Responda com 1 ou 2.'
        );
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
            $target = $this->getTargetAlunoFromContext($context);
            if ($target) {
                $context = $this->setTargetAlunoInContext($context, $target, (string) ($context['target_source'] ?? 'cpf'));

                return $this->resumeAfterTargetSelection(
                    $conversation,
                    $context,
                    BotState::CURSO_ALUNO_CONFIRM,
                    [
                        'selected_evento_id' => $context['selected_evento_id'] ?? null,
                        'selected_event_id' => $context['selected_event_id'] ?? null,
                    ]
                );
            }

            $alunoPhone = $this->findAlunoByPhone((string) ($conversation->from ?? ''));
            if ($alunoPhone) {
                return $this->startTargetPick(
                    $conversation,
                    $context,
                    $alunoPhone,
                    BotState::CURSO_ALUNO_CONFIRM,
                    [
                        'selected_evento_id' => $context['selected_evento_id'] ?? null,
                        'selected_event_id' => $context['selected_event_id'] ?? null,
                    ]
                );
            }

            $this->setConversationState($conversation, BotState::CURSO_CPF, $context);

            return 'Para se inscrever pelo WhatsApp, informe seu CPF (somente números).';
        }

        if ($option === 2) {
            return $this->listCourses($conversation);
        }

        return $this->buildOptionsMessage(
            ['Opção inválida.'],
            [
                '1) Inscrever pelo WhatsApp (CPF)',
                '2) Voltar',
            ],
            'Responda com 1 ou 2.'
        );
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

        if (! $aluno) {
            $context['aluno_cpf'] = $cpf;
            $context['aluno_mode'] = 'create';
            $context['aluno_origin_state'] = BotState::CURSO_ALUNO_CONFIRM;
            $context['wizard_return_state'] = BotState::CURSO_ALUNO_CONFIRM;
            $context['wizard_return_payload'] = [
                'selected_evento_id' => $context['selected_evento_id'] ?? null,
                'selected_event_id' => $context['selected_event_id'] ?? null,
            ];
            $context['wizard_set_target'] = true;
            $context['wizard_back_state'] = BotState::CURSO_CPF;
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context, 'Não encontrei seu cadastro. Vamos fazer seu cadastro rapidinho.');
        }

        $context['selected_evento_id'] = $evento->id;
        $context['selected_event_id'] = $evento->id;
        $context = $this->setTargetAlunoInContext($context, $aluno, 'cpf');
        $context = $this->clearAlunoEditContext($context);

        return $this->resumeAfterTargetSelection(
            $conversation,
            $context,
            BotState::CURSO_ALUNO_CONFIRM,
            [
                'selected_evento_id' => $evento->id,
                'selected_event_id' => $evento->id,
            ]
        );
    }

    private function handleCourseAlunoConfirmInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $evento = $this->getSelectedEventoFromContext($context);
        $aluno = $this->getSelectedAlunoFromContext($context);

        if (! $evento || ! $evento->curso) {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Este curso não está mais disponível para inscrição.'
            );
        }

        if (! $aluno) {
            logger()->error('BOT inscrição falhou', [
                'channel' => (string) ($conversation->channel ?? ''),
                'selected_event_id' => (int) ($context['selected_event_id'] ?? $context['selected_evento_id'] ?? 0),
                'target_aluno_id' => (int) ($context['target_aluno_id'] ?? 0),
                'cpf_masked' => $this->maskCpfForLog((string) ($context['target_cpf'] ?? $context['aluno_cpf'] ?? '')),
                'motivo' => 'aluno_nao_localizado_no_contexto',
                'bot_provider' => (string) $this->configuracaoService->get('bot.provider', 'meta'),
                'bot_credentials_mode' => (string) $this->configuracaoService->get(
                    'bot.credentials_mode',
                    'inherit_notifications'
                ),
            ]);

            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível localizar os dados para concluir sua inscrição.'
            );
        }

        $option = $this->parseNumericOption($text);
        if ($option === 1) {
            try {
                return $this->executeCourseEnrollment($conversation, $evento, $aluno, $context);
            } catch (Throwable $exception) {
                logger()->error('BOT inscrição falhou', $this->buildEnrollmentLogContext(
                    $conversation,
                    $evento,
                    $aluno,
                    $context,
                    null,
                    $exception
                ));

                return $this->respondWithMenu(
                    $conversation,
                    false,
                    'Não foi possível concluir sua inscrição agora. Tente novamente em instantes.'
                );
            }
        }

        if ($option === 2) {
            $context['edit_fields'] = $this->getAlunoEditFields();
            $context['edit_index'] = 0;
            $context['edit_values'] = [];
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoEditFieldPrompt($context);
        }

        if ($option === 3) {
            $context = $this->clearAlunoEditContext($context);

            return $this->listCoursesWithPrefix($conversation, 'Selecione outro curso para continuar.');
        }

        return $this->buildAlunoConfirmMessage(
            $context['aluno_snapshot'] ?? [],
            'Opção inválida. Escolha 1, 2 ou 3.'
        );
    }

    private function handleCourseAlunoEditFieldInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $fields = $context['edit_fields'] ?? [];
        $index = (int) ($context['edit_index'] ?? 0);

        if (! is_array($fields) || $fields === []) {
            $context['edit_fields'] = $this->getAlunoEditFields();
            $context['edit_index'] = 0;
            $context['edit_values'] = [];
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoEditFieldPrompt($context);
        }

        if (trim($text) === '3') {
            $context = $this->clearAlunoEditContext($context);
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

            return $this->buildAlunoConfirmMessage($context['aluno_snapshot'] ?? []);
        }

        if ($index < 0 || $index >= count($fields)) {
            $context['edit_index'] = 0;
            $context['edit_values'] = [];
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoEditFieldPrompt($context);
        }

        $field = $fields[$index];
        if (! is_array($field) || ! isset($field['key'])) {
            $context['edit_index'] = 0;
            $context['edit_values'] = [];
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoEditFieldPrompt($context);
        }

        $validation = $this->validateAlunoEditValue((string) $field['key'], $text);
        if (($validation['ok'] ?? false) !== true) {
            $error = (string) ($validation['message'] ?? 'Valor inválido.');

            return $this->buildAlunoEditFieldPrompt($context, $error);
        }

        $editValues = $context['edit_values'] ?? [];
        if (! is_array($editValues)) {
            $editValues = [];
        }
        $editValues[(string) $field['key']] = $validation['value'] ?? null;

        $context['edit_values'] = $editValues;
        $context['edit_index'] = $index + 1;

        if ($context['edit_index'] >= count($fields)) {
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_REVIEW, $context);

            return $this->buildAlunoEditReviewMessage($context);
        }

        $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

        return $this->buildAlunoEditFieldPrompt($context);
    }

    private function handleCourseAlunoEditReviewInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            $aluno = $this->getSelectedAlunoFromContext($context);
            if (! $aluno) {
                return $this->respondWithMenu($conversation, false, 'Não foi possível localizar seu cadastro.');
            }

            $editValues = $context['edit_values'] ?? [];
            if (! is_array($editValues) || $editValues === []) {
                $context = $this->clearAlunoEditContext($context);
                $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

                return $this->buildAlunoConfirmMessage($context['aluno_snapshot'] ?? []);
            }

            $payload = $this->sanitizeAlunoUpdatePayload($editValues);

            if ($payload !== []) {
                try {
                    $aluno->fill($payload);
                    $aluno->save();
                } catch (Throwable) {
                    return $this->buildAlunoEditReviewMessage(
                        $context,
                        'Não foi possível atualizar seus dados. Revise as informações.'
                    );
                }
            }

            $aluno->refresh();
            $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);
            $context = $this->clearAlunoEditContext($context);
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

            return $this->buildAlunoConfirmMessage(
                $context['aluno_snapshot'],
                'Dados atualizados. Confirme para concluir a inscrição.'
            );
        }

        if ($option === 2) {
            $context['edit_values'] = [];
            $context['edit_index'] = 0;
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoEditFieldPrompt($context);
        }

        if ($option === 3) {
            $context = $this->clearAlunoEditContext($context);
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

            return $this->buildAlunoConfirmMessage($context['aluno_snapshot'] ?? []);
        }

        return $this->buildAlunoEditReviewMessage($context, 'Opção inválida. Escolha 1, 2 ou 3.');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function executeCourseEnrollment(
        BotConversation $conversation,
        EventoCurso $evento,
        Aluno $aluno,
        array $context = []
    ): string
    {
        try {
            $resultado = $this->matriculaService->solicitarInscricao($aluno->id, $evento->id);
            $this->logEnrollmentDebug($conversation, $evento, $aluno, $context, $resultado, null);
        } catch (Throwable $exception) {
            logger()->error('BOT inscrição falhou', $this->buildEnrollmentLogContext(
                $conversation,
                $evento,
                $aluno,
                $context,
                null,
                $exception
            ));

            $exceptionMessage = mb_strtolower(trim($exception->getMessage()));
            if (str_contains($exceptionMessage, 'evento não encontrado')
                || str_contains($exceptionMessage, 'evento nao encontrado')
                || str_contains($exceptionMessage, 'não encontrado')
                || str_contains($exceptionMessage, 'nao encontrado')
                || str_contains($exceptionMessage, 'inscri')
                || str_contains($exceptionMessage, 'indispon')
            ) {
                return $this->respondWithMenu(
                    $conversation,
                    false,
                    'Este curso não está mais disponível para inscrição.'
                );
            }

            if (str_contains($exceptionMessage, 'limite de vagas atingido')
                || str_contains($exceptionMessage, 'vaga')
            ) {
                return $this->respondWithMenu(
                    $conversation,
                    false,
                    'No momento não há vagas disponíveis.'
                );
            }

            if (str_contains($exceptionMessage, 'já possui')
                || str_contains($exceptionMessage, 'ja possui')
                || str_contains($exceptionMessage, 'duplicate')
                || str_contains($exceptionMessage, 'duplic')
            ) {
                return $this->respondWithMenu(
                    $conversation,
                    false,
                    'Você já possui inscrição neste curso.'
                );
            }
            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível concluir sua inscrição agora. Tente novamente em instantes.'
            );
        }

        if (! is_array($resultado) || ! isset($resultado['tipo'])) {
            logger()->error('BOT inscrição falhou', $this->buildEnrollmentLogContext(
                $conversation,
                $evento,
                $aluno,
                $context,
                is_array($resultado) ? $resultado : null,
                null
            ));

            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível concluir sua inscrição agora. Tente novamente em instantes.'
            );
        }

        $serviceStatus = (string) ($resultado['status'] ?? '');

        if ($serviceStatus === 'already_enrolled') {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Você já possui inscrição neste curso.'
            );
        }

        if ($serviceStatus === 'waitlist') {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Você já está na lista de espera deste curso.'
            );
        }

        if ($serviceStatus === 'unavailable') {
            return $this->respondWithMenu(
                $conversation,
                false,
                'Este curso não está mais disponível para inscrição.'
            );
        }

        if ($serviceStatus === 'no_vacancies' || ($resultado['tipo'] ?? null) === 'lista_espera') {
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
            logger()->error('BOT inscrição falhou', $this->buildEnrollmentLogContext(
                $conversation,
                $evento,
                $aluno,
                $context,
                $resultado,
                null
            ));

            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível concluir sua inscrição agora.'
            );
        }

        if (! in_array($registro->status, [StatusMatricula::Pendente, StatusMatricula::Confirmada], true)) {
            $status = mb_strtolower((string) ($registro->status->value ?? $registro->status));
            // Status inativos não devem ser tratados como "já inscrito".

            logger()->error('BOT inscrição falhou', $this->buildEnrollmentLogContext(
                $conversation,
                $evento,
                $aluno,
                $context,
                $resultado,
                null
            ));

            return $this->respondWithMenu(
                $conversation,
                false,
                'Não foi possível concluir sua inscrição com este CPF.'
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

    /**
     * @return array<string, string>
     */
    private function buildAlunoSnapshot(Aluno $aluno): array
    {
        $celular = trim((string) ($aluno->celular ?? ''));
        $telefone = trim((string) ($aluno->telefone ?? ''));
        $contatoPrincipal = $celular !== '' ? $celular : $telefone;
        $sexo = $aluno->sexo instanceof Sexo ? $aluno->sexo->value : (string) ($aluno->sexo ?? '');
        $dataNascimento = $aluno->data_nascimento?->format('d/m/Y') ?? '';

        return [
            'nome_completo' => trim((string) ($aluno->nome_completo ?? '')),
            'cpf' => Cpf::format((string) ($aluno->cpf ?? '')),
            'celular' => Phone::format($celular),
            'telefone' => Phone::format($telefone),
            'contato' => Phone::format($contatoPrincipal),
            'email' => trim((string) ($aluno->email ?? '')),
            'data_nascimento' => $dataNascimento,
            'sexo' => $sexo,
            'sexo_label' => $this->getSexoLabel($sexo),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getSelectedAlunoFromContext(array $context): ?Aluno
    {
        $targetAlunoId = (int) ($context['target_aluno_id'] ?? 0);
        if ($targetAlunoId > 0) {
            $targetAluno = Aluno::query()->find($targetAlunoId);
            if ($targetAluno) {
                return $targetAluno;
            }
        }

        $alunoId = (int) ($context['aluno_id'] ?? 0);
        if ($alunoId <= 0) {
            return null;
        }

        return Aluno::query()->find($alunoId);
    }

    /**
     * @return list<array{key: string, prompt: string}>
     */
    private function getAlunoEditFields(): array
    {
        $fields = [];

        if (Schema::hasColumn('alunos', 'nome_completo')) {
            $fields[] = ['key' => 'nome_completo', 'prompt' => 'Informe seu nome completo:'];
        }

        if (Schema::hasColumn('alunos', 'celular')) {
            $fields[] = ['key' => 'celular', 'prompt' => 'Informe seu telefone/celular:'];
        } elseif (Schema::hasColumn('alunos', 'telefone')) {
            $fields[] = ['key' => 'telefone', 'prompt' => 'Informe seu telefone/celular:'];
        }

        if (Schema::hasColumn('alunos', 'email')) {
            $fields[] = ['key' => 'email', 'prompt' => 'Informe seu e-mail:'];
        }

        if (Schema::hasColumn('alunos', 'data_nascimento')) {
            $fields[] = ['key' => 'data_nascimento', 'prompt' => 'Informe sua data de nascimento (dd/mm/AAAA):'];
        }

        if (Schema::hasColumn('alunos', 'sexo')) {
            $fields[] = ['key' => 'sexo', 'prompt' => $this->buildSexoPrompt()];
        }

        if ($fields === []) {
            $fields[] = ['key' => 'nome_completo', 'prompt' => 'Informe seu nome completo:'];
            $fields[] = ['key' => 'celular', 'prompt' => 'Informe seu telefone/celular:'];
            $fields[] = ['key' => 'data_nascimento', 'prompt' => 'Informe sua data de nascimento (dd/mm/AAAA):'];
            $fields[] = ['key' => 'sexo', 'prompt' => $this->buildSexoPrompt()];
        }

        return $fields;
    }

    /**
     * @param array<string, string> $snapshot
     */
    private function buildAlunoConfirmMessage(array $snapshot, ?string $prefix = null): string
    {
        $headerLines = [];

        if ($prefix !== null && trim($prefix) !== '') {
            $headerLines[] = trim($prefix);
            $headerLines[] = '';
        }

        $headerLines[] = 'Encontrei seu cadastro. Confira seus dados:';
        $headerLines[] = 'Nome: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado');
        $headerLines[] = 'CPF: ' . (($snapshot['cpf'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Telefone: ' . (($snapshot['contato'] ?? '') ?: 'Não informado');
        $headerLines[] = 'E-mail: ' . (($snapshot['email'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Data de nascimento: ' . (($snapshot['data_nascimento'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Sexo: ' . (($snapshot['sexo_label'] ?? '') ?: 'Não informado');

        return $this->buildOptionsMessage(
            $headerLines,
            [
                '1) Confirmar inscrição',
                '2) Corrigir informações',
                '3) Voltar',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildAlunoEditFieldPrompt(array $context, ?string $prefix = null): string
    {
        $fields = $context['edit_fields'] ?? [];
        $index = (int) ($context['edit_index'] ?? 0);
        $prompt = 'Informe o valor do campo:';

        if (is_array($fields) && isset($fields[$index]) && is_array($fields[$index]) && isset($fields[$index]['prompt'])) {
            $prompt = (string) $fields[$index]['prompt'];
        }

        $lines = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $lines[] = trim($prefix);
        }
        $lines[] = $prompt;
        $lines[] = 'Digite 3 para voltar.';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildAlunoEditReviewMessage(array $context, ?string $prefix = null): string
    {
        $snapshot = $context['aluno_snapshot'] ?? [];
        if (! is_array($snapshot)) {
            $snapshot = [];
        }

        $editValues = $context['edit_values'] ?? [];
        if (! is_array($editValues)) {
            $editValues = [];
        }

        foreach ($editValues as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $scalarValue = $value === null ? '' : (string) $value;
            if (in_array($key, ['celular', 'telefone'], true)) {
                $snapshot[$key] = Phone::format($scalarValue);
                $snapshot['contato'] = Phone::format($scalarValue);

                continue;
            }

            if ($key === 'data_nascimento') {
                $snapshot[$key] = $this->formatDataNascimentoForDisplay($scalarValue);

                continue;
            }

            if ($key === 'sexo') {
                $snapshot[$key] = $scalarValue;
                $snapshot['sexo_label'] = $this->getSexoLabel($scalarValue);

                continue;
            }

            $snapshot[$key] = $scalarValue;
        }

        $headerLines = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $headerLines[] = trim($prefix);
            $headerLines[] = '';
        }

        $headerLines[] = 'Confira os dados atualizados:';
        $headerLines[] = 'Nome: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado');
        $headerLines[] = 'CPF: ' . (($snapshot['cpf'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Telefone: ' . (($snapshot['contato'] ?? '') ?: 'Não informado');
        $headerLines[] = 'E-mail: ' . (($snapshot['email'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Data de nascimento: ' . (($snapshot['data_nascimento'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Sexo: ' . (($snapshot['sexo_label'] ?? '') ?: 'Não informado');

        return $this->buildOptionsMessage(
            $headerLines,
            [
                '1) Confirmar atualização',
                '2) Refazer correção',
                '3) Voltar',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @return array{ok: bool, value?: string, message?: string}
     */
    private function validateAlunoEditValue(string $fieldKey, string $text): array
    {
        $value = trim($text);

        if ($fieldKey === 'nome_completo') {
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
            if (mb_strlen($value) < 5) {
                return ['ok' => false, 'message' => 'Nome inválido. Informe ao menos 5 caracteres.'];
            }

            return ['ok' => true, 'value' => $value];
        }

        if (in_array($fieldKey, ['celular', 'telefone'], true)) {
            $phone = Phone::normalize($value);
            if ($phone === '' || preg_match('/^\d{10,13}$/', $phone) !== 1) {
                return ['ok' => false, 'message' => 'Telefone inválido. Envie apenas números (10 a 13 dígitos).'];
            }

            return ['ok' => true, 'value' => $phone];
        }

        if ($fieldKey === 'email') {
            $email = mb_strtolower($value);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return ['ok' => false, 'message' => 'E-mail inválido. Informe um e-mail válido.'];
            }

            return ['ok' => true, 'value' => $email];
        }

        if ($fieldKey === 'data_nascimento') {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) !== 1) {
                return ['ok' => false, 'message' => 'Data inválida. Use o formato dd/mm/AAAA.'];
            }

            $parts = explode('/', $value);
            $day = isset($parts[0]) ? (int) $parts[0] : 0;
            $month = isset($parts[1]) ? (int) $parts[1] : 0;
            $year = isset($parts[2]) ? (int) $parts[2] : 0;

            if (! checkdate($month, $day, $year)) {
                return ['ok' => false, 'message' => 'Data inválida. Informe uma data real.'];
            }

            return ['ok' => true, 'value' => sprintf('%04d-%02d-%02d', $year, $month, $day)];
        }

        if ($fieldKey === 'sexo') {
            $sexo = $this->normalizeSexoInput($value);
            if ($sexo === null) {
                return ['ok' => false, 'message' => 'Sexo inválido. Selecione uma opção da lista.'];
            }

            return ['ok' => true, 'value' => $sexo];
        }

        if ($value === '') {
            return ['ok' => false, 'message' => 'Valor inválido. Tente novamente.'];
        }

        return ['ok' => true, 'value' => $value];
    }

    /**
     * @param array<string, mixed> $editValues
     * @return array<string, mixed>
     */
    private function sanitizeAlunoUpdatePayload(array $editValues): array
    {
        $allowedKeys = array_map(
            static fn (array $field): string => (string) ($field['key'] ?? ''),
            $this->getAlunoEditFields()
        );

        $payload = [];
        foreach ($editValues as $key => $value) {
            if (! is_string($key) || ! in_array($key, $allowedKeys, true)) {
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $payload[$key] = $value === null ? null : trim((string) $value);
        }

        return $payload;
    }

    private function buildSexoPrompt(): string
    {
        $lines = ['Selecione seu sexo:'];

        foreach ($this->getSexoOptions() as $index => $option) {
            $lines[] = $this->toEmojiNumber($index + 1) . ' ' . $option['label'];
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function getSexoOptions(): array
    {
        $options = [];
        foreach (Sexo::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $this->getSexoLabel($case->value),
            ];
        }

        if ($options === []) {
            return [
                ['value' => 'masculino', 'label' => 'Masculino'],
                ['value' => 'feminino', 'label' => 'Feminino'],
                ['value' => 'nao_declarado', 'label' => 'Prefiro não informar'],
            ];
        }

        return $options;
    }

    private function getSexoLabel(string $value): string
    {
        return match ($value) {
            Sexo::Masculino->value => 'Masculino',
            Sexo::Feminino->value => 'Feminino',
            Sexo::NaoDeclarado->value => 'Prefiro não informar',
            default => 'Não informado',
        };
    }

    private function normalizeSexoInput(string $value): ?string
    {
        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        $numeric = $this->parseNumericOption($normalized);
        if ($numeric !== null) {
            $options = $this->getSexoOptions();
            if (isset($options[$numeric - 1])) {
                return $options[$numeric - 1]['value'];
            }
        }

        $normalized = preg_replace('/\s+/u', '_', $normalized) ?? $normalized;
        $normalized = str_replace(['-', '.'], '_', $normalized);

        $direct = array_map(static fn (Sexo $case) => $case->value, Sexo::cases());
        if (in_array($normalized, $direct, true)) {
            return $normalized;
        }

        return match ($normalized) {
            'm', 'masc', 'masculino' => Sexo::Masculino->value,
            'f', 'fem', 'feminino' => Sexo::Feminino->value,
            'nao_declarado', 'nao_declarada', 'prefiro_nao_informar', 'nao_informar' => Sexo::NaoDeclarado->value,
            default => null,
        };
    }

    private function formatDataNascimentoForDisplay(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $normalized) === 1) {
            return $normalized;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) !== 1) {
            return $normalized;
        }

        $parts = explode('-', $normalized);
        if (count($parts) !== 3) {
            return $normalized;
        }

        return sprintf('%02d/%02d/%04d', (int) $parts[2], (int) $parts[1], (int) $parts[0]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function clearAlunoEditContext(array $context): array
    {
        unset(
            $context['edit_fields'],
            $context['edit_index'],
            $context['edit_values']
        );

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getTargetAlunoFromContext(array $context): ?Aluno
    {
        $id = (int) ($context['target_aluno_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return Aluno::query()->find($id);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function setTargetAlunoInContext(array $context, Aluno $aluno, string $source): array
    {
        $context['target_aluno_id'] = (int) $aluno->id;
        $context['target_cpf'] = Cpf::normalize((string) ($aluno->cpf ?? ''));
        $context['target_source'] = $source;
        $context['aluno_id'] = (int) $aluno->id;
        $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);

        return $context;
    }

    private function findAlunoByPhone(string $phone): ?Aluno
    {
        $normalized = Phone::normalize($phone);
        if ($normalized === '') {
            return null;
        }

        $candidates = $this->buildPhoneCandidates($normalized);
        if ($candidates === []) {
            return null;
        }

        /** @var Collection<int, Aluno> $matches */
        $matches = Aluno::query()
            ->where(function ($query) use ($candidates) {
                $query->whereIn('celular', $candidates)
                    ->orWhereIn('telefone', $candidates);
            })
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        return $matches->first();
    }

    /**
     * @return list<string>
     */
    private function buildPhoneCandidates(string $normalized): array
    {
        $candidates = [$normalized];

        if (strlen($normalized) > 11) {
            $candidates[] = substr($normalized, -11);
        }

        if (strlen($normalized) > 10) {
            $candidates[] = substr($normalized, -10);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $returnPayload
     */
    private function startTargetPick(
        BotConversation $conversation,
        array $context,
        Aluno $aluno,
        string $returnState,
        array $returnPayload = []
    ): string {
        $context['target_pick_aluno_id'] = (int) $aluno->id;
        $context['target_pick_snapshot'] = $this->buildAlunoSnapshot($aluno);
        $context['return_state'] = $returnState;
        $context['return_payload'] = $returnPayload;
        $this->setConversationState($conversation, BotState::TARGET_PICK, $context);

        return $this->buildTargetPickMessage($context['target_pick_snapshot']);
    }

    /**
     * @param array<string, string> $snapshot
     */
    private function buildTargetPickMessage(array $snapshot): string
    {
        return $this->buildOptionsMessage(
            [
                '📌 Encontrei um cadastro vinculado a este número:',
                '',
                'Nome: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado'),
                'CPF: ' . $this->maskCpf((string) ($snapshot['cpf'] ?? '')),
                '',
                'O atendimento é para esta pessoa?',
            ],
            [
                '1) Atender esta pessoa',
                '2) Atender outra pessoa (informar CPF)',
                '3) Voltar',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    private function handleTargetPickInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $option = $this->parseNumericOption($text);
        $returnState = (string) ($context['return_state'] ?? '');
        $returnPayload = $context['return_payload'] ?? [];
        if (! is_array($returnPayload)) {
            $returnPayload = [];
        }

        if ($option === 1) {
            $alunoId = (int) ($context['target_pick_aluno_id'] ?? 0);
            $aluno = $alunoId > 0 ? Aluno::query()->find($alunoId) : null;
            if (! $aluno) {
                $this->setConversationState($conversation, BotState::TARGET_OTHER_CPF, $context);

                return 'Informe o CPF da pessoa atendida (somente números).';
            }

            $context = $this->setTargetAlunoInContext($context, $aluno, 'phone');
            unset($context['target_pick_aluno_id'], $context['target_pick_snapshot']);

            return $this->resumeAfterTargetSelection($conversation, $context, $returnState, $returnPayload);
        }

        if ($option === 2) {
            $this->setConversationState($conversation, BotState::TARGET_OTHER_CPF, $context);

            return 'Informe o CPF da pessoa atendida (somente números).';
        }

        if ($option === 3) {
            if ($returnState !== '') {
                return $this->resumeWithoutTarget($conversation, $context, $returnState, $returnPayload);
            }

            return $this->respondWithMenu($conversation, false);
        }

        return $this->buildTargetPickMessage(is_array($context['target_pick_snapshot'] ?? null) ? $context['target_pick_snapshot'] : []);
    }

    private function handleTargetOtherCpfInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $returnState = (string) ($context['return_state'] ?? '');
        $returnPayload = $context['return_payload'] ?? [];
        if (! is_array($returnPayload)) {
            $returnPayload = [];
        }

        $option = $this->parseNumericOption($text);
        if ($option === 0) {
            $this->setConversationState($conversation, BotState::TARGET_PICK, $context);

            return $this->buildTargetPickMessage(is_array($context['target_pick_snapshot'] ?? null) ? $context['target_pick_snapshot'] : []);
        }

        $cpf = Cpf::normalize($text);
        if ($cpf === '') {
            return 'CPF não informado. Envie apenas números.';
        }

        if (! Cpf::isValid($cpf)) {
            return 'CPF inválido. Envie apenas números.';
        }

        $aluno = $this->findAlunoByCpf($cpf);
        if (! $aluno) {
            $context['target_other_cpf'] = $cpf;
            $this->setConversationState($conversation, BotState::TARGET_OTHER_NOT_FOUND, $context);

            return $this->buildOptionsMessage(
                ['Não encontrei cadastro para o CPF informado. O que deseja fazer?'],
                [
                    '1) Cadastrar esta pessoa agora',
                    '2) Informar outro CPF',
                    '3) Voltar',
                ],
                'Responda com 1, 2 ou 3.'
            );
        }

        $context = $this->setTargetAlunoInContext($context, $aluno, 'cpf');
        unset($context['target_other_cpf'], $context['target_pick_aluno_id'], $context['target_pick_snapshot']);

        return $this->resumeAfterTargetSelection($conversation, $context, $returnState, $returnPayload);
    }

    private function handleTargetOtherNotFoundInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $option = $this->parseNumericOption($text);
        $cpf = Cpf::normalize((string) ($context['target_other_cpf'] ?? ''));

        if ($option === 1) {
            if ($cpf === '' || ! Cpf::isValid($cpf)) {
                $this->setConversationState($conversation, BotState::TARGET_OTHER_CPF, $context);

                return 'Informe o CPF da pessoa atendida (somente números).';
            }

            $context['aluno_cpf'] = $cpf;
            $context['aluno_mode'] = 'create';
            $context['aluno_origin_state'] = BotState::TARGET_OTHER_NOT_FOUND;
            $context['wizard_return_state'] = (string) ($context['return_state'] ?? '');
            $context['wizard_return_payload'] = is_array($context['return_payload'] ?? null) ? $context['return_payload'] : [];
            $context['wizard_set_target'] = true;
            $context['wizard_back_state'] = BotState::TARGET_OTHER_NOT_FOUND;
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context, 'Vamos cadastrar esta pessoa agora.');
        }

        if ($option === 2) {
            $this->setConversationState($conversation, BotState::TARGET_OTHER_CPF, $context);

            return 'Informe o CPF da pessoa atendida (somente números).';
        }

        if ($option === 3) {
            $this->setConversationState($conversation, BotState::TARGET_OTHER_CPF, $context);

            return 'Informe o CPF da pessoa atendida (somente números).';
        }

        return $this->buildOptionsMessage(
            ['Não encontrei cadastro para o CPF informado. O que deseja fazer?'],
            [
                '1) Cadastrar esta pessoa agora',
                '2) Informar outro CPF',
                '3) Voltar',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $returnPayload
     */
    private function resumeAfterTargetSelection(
        BotConversation $conversation,
        array $context,
        string $returnState,
        array $returnPayload,
        ?string $prefix = null
    ): string {
        unset($context['target_pick_aluno_id'], $context['target_pick_snapshot'], $context['target_other_cpf']);
        foreach ($returnPayload as $key => $value) {
            if (is_string($key)) {
                $context[$key] = $value;
            }
        }

        if ($returnState === BotState::CURSO_ALUNO_CONFIRM) {
            $aluno = $this->getTargetAlunoFromContext($context);
            $evento = $this->getSelectedEventoFromContext($context);
            if (! $aluno || ! $evento || ! $evento->curso) {
                return $this->respondWithMenu($conversation, false, 'Não foi possível retomar a inscrição.');
            }

            $context['aluno_id'] = (int) $aluno->id;
            $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);
            $context = $this->clearAlunoEditContext($context);
            $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

            return $this->buildAlunoConfirmMessage($context['aluno_snapshot'], $prefix);
        }

        if ($returnState === BotState::ALUNO_MENU) {
            $aluno = $this->getTargetAlunoFromContext($context);
            if (! $aluno) {
                return $this->askAlunoCpf($conversation);
            }

            $context['aluno_id'] = (int) $aluno->id;
            $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);
            $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

            return $this->buildAlunoMenuMessage($context['aluno_snapshot'], $prefix ?: 'Atendimento iniciado.');
        }

        if ($returnState === BotState::CANCEL_LIST) {
            $aluno = $this->getTargetAlunoFromContext($context);
            if (! $aluno) {
                return $this->askCancelCpf($conversation);
            }

            $context['cancel_cpf'] = Cpf::normalize((string) ($aluno->cpf ?? ''));
            $items = $this->buscarItensParaCancelamento((int) $aluno->id);
            if ($items === []) {
                return $this->respondWithMenu(
                    $conversation,
                    false,
                    'Nenhuma inscrição elegível para cancelamento foi encontrada.'
                );
            }

            $context['cancel_items'] = $items;
            $this->setConversationState($conversation, BotState::CANCEL_LIST, $context);
            $lines = $this->buildCancelListLines($items);

            $parts = [];
            if ($prefix !== null && trim($prefix) !== '') {
                $parts[] = trim($prefix);
                $parts[] = '';
            }

            $parts[] = 'Inscrições encontradas:';
            $parts = [...$parts, ...$lines];
            $parts[] = '0️⃣ Voltar';
            $parts[] = '';
            $parts[] = 'Digite o número da inscrição que deseja cancelar.';

            return implode("\n", $parts);
        }

        return $this->respondWithMenu($conversation, false);
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $returnPayload
     */
    private function resumeWithoutTarget(
        BotConversation $conversation,
        array $context,
        string $returnState,
        array $returnPayload
    ): string {
        unset($context['target_pick_aluno_id'], $context['target_pick_snapshot'], $context['target_other_cpf']);
        foreach ($returnPayload as $key => $value) {
            if (is_string($key)) {
                $context[$key] = $value;
            }
        }

        if ($returnState === BotState::CURSO_ALUNO_CONFIRM) {
            $this->setConversationState($conversation, BotState::CURSO_ACTION, $context);

            return $this->buildOptionsMessage(
                ['O que você deseja fazer?'],
                [
                    '1) Inscrever pelo WhatsApp (CPF)',
                    '2) Voltar',
                ],
                'Responda com 1 ou 2.'
            );
        }

        if ($returnState === BotState::ALUNO_MENU) {
            return $this->respondWithMenu($conversation, false);
        }

        if ($returnState === BotState::CANCEL_LIST) {
            $this->setConversationState($conversation, BotState::CANCEL_CPF, $context);

            return 'Informe o CPF com 11 números para localizar suas inscrições.';
        }

        return $this->respondWithMenu($conversation, false);
    }

    private function maskCpf(string $cpf): string
    {
        $normalized = Cpf::normalize($cpf);
        if ($normalized === '' || strlen($normalized) < 2) {
            return '***';
        }

        return '***.***.***-' . substr($normalized, -2);
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $resultado
     * @return array<string, mixed>
     */
    private function buildEnrollmentLogContext(
        BotConversation $conversation,
        EventoCurso $evento,
        Aluno $aluno,
        array $context,
        ?array $resultado = null,
        ?Throwable $exception = null
    ): array {
        $selectedEventId = (int) ($context['selected_event_id'] ?? 0);
        if ($selectedEventId <= 0) {
            $selectedEventId = (int) ($context['selected_evento_id'] ?? 0);
        }
        if ($selectedEventId <= 0) {
            $selectedEventId = (int) $evento->id;
        }

        $targetAlunoId = (int) ($context['target_aluno_id'] ?? 0);
        if ($targetAlunoId <= 0) {
            $targetAlunoId = (int) $aluno->id;
        }

        $resultadoLog = null;
        if (is_array($resultado)) {
            $registro = $resultado['registro'] ?? null;
            $resultadoLog = [
                'status' => $resultado['status'] ?? null,
                'tipo' => $resultado['tipo'] ?? null,
                'registro_class' => is_object($registro) ? $registro::class : gettype($registro),
                'registro_status' => is_object($registro) && isset($registro->status)
                    ? (string) (($registro->status->value ?? $registro->status))
                    : null,
                'aluno_id' => $resultado['debug']['aluno_id'] ?? null,
                'evento_curso_id' => $resultado['debug']['evento_curso_id'] ?? null,
                'matricula_encontrada' => $resultado['debug']['matricula_encontrada'] ?? null,
                'lista_espera_encontrada' => $resultado['debug']['lista_espera_encontrada'] ?? null,
            ];
        }

        $payload = [
            'channel' => (string) ($conversation->channel ?? ''),
            'selected_event_id' => $selectedEventId,
            'target_aluno_id' => $targetAlunoId,
            'cpf_masked' => $this->maskCpfForLog((string) ($aluno->cpf ?? ($context['target_cpf'] ?? ''))),
            'bot_provider' => (string) $this->configuracaoService->get('bot.provider', 'meta'),
            'bot_credentials_mode' => (string) $this->configuracaoService->get(
                'bot.credentials_mode',
                'inherit_notifications'
            ),
            'resultado' => $resultadoLog,
        ];

        if ($exception !== null) {
            $payload['exception_message'] = $exception->getMessage();
            $payload['exception_class'] = $exception::class;
            $payload['exception'] = $exception;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $resultado
     */
    private function logEnrollmentDebug(
        BotConversation $conversation,
        EventoCurso $evento,
        Aluno $aluno,
        array $context,
        ?array $resultado,
        ?Throwable $exception
    ): void {
        if (! (bool) $this->configuracaoService->get('bot.debug', false)) {
            return;
        }

        logger()->info('BOT inscrição diagnóstico', $this->buildEnrollmentLogContext(
            $conversation,
            $evento,
            $aluno,
            $context,
            $resultado,
            $exception
        ));
    }

    private function maskCpfForLog(string $cpf): string
    {
        $normalized = Cpf::normalize($cpf);
        if ($normalized === '') {
            return '***';
        }

        return '***' . substr($normalized, -3);
    }

    private function askAlunoCpf(BotConversation $conversation): string
    {
        $context = $this->getConversationContext($conversation);
        $target = $this->getTargetAlunoFromContext($context);
        if ($target) {
            $context = $this->setTargetAlunoInContext($context, $target, (string) ($context['target_source'] ?? 'cpf'));
            $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

            return $this->buildAlunoMenuMessage($context['aluno_snapshot'], 'Atendimento iniciado para a pessoa selecionada.');
        }

        $alunoPhone = $this->findAlunoByPhone((string) ($conversation->from ?? ''));
        if ($alunoPhone) {
            return $this->startTargetPick($conversation, $context, $alunoPhone, BotState::ALUNO_MENU);
        }

        $this->setConversationState($conversation, BotState::ALUNO_CPF, $context);

        return 'Informe seu CPF (somente números).';
    }

    private function handleAlunoCpfInput(BotConversation $conversation, string $text): string
    {
        $cpf = Cpf::normalize($text);
        if ($cpf === '') {
            return 'CPF não informado. Envie apenas números.';
        }

        if (! Cpf::isValid($cpf)) {
            return 'CPF inválido. Envie apenas números.';
        }

        $aluno = $this->findAlunoByCpf($cpf);
        if (! $aluno) {
            $context = [
                'aluno_cpf' => $cpf,
                'aluno_mode' => 'create',
                'aluno_origin_state' => BotState::ALUNO_MENU,
                'wizard_return_state' => BotState::ALUNO_MENU,
                'wizard_return_payload' => [],
                'wizard_set_target' => true,
                'wizard_back_state' => BotState::ALUNO_CPF,
            ];

            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context, 'Não encontrei seu cadastro. Vamos criar seu cadastro agora.');
        }

        $context = $this->setTargetAlunoInContext($this->getConversationContext($conversation), $aluno, 'cpf');
        $context['aluno_cpf'] = Cpf::normalize((string) $aluno->cpf);

        $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

        return $this->buildAlunoMenuMessage($context['aluno_snapshot'], 'Cadastro localizado com sucesso.');
    }

    private function handleAlunoMenuInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $aluno = $this->getSelectedAlunoFromContext($context);

        if (! $aluno) {
            return $this->askAlunoCpf($conversation);
        }

        $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            $this->setConversationState($conversation, BotState::ALUNO_VIEW_DATA, $context);

            return $this->buildAlunoDataViewMessage($context['aluno_snapshot']);
        }

        if ($option === 2) {
            $items = $this->buscarItensParaCancelamento((int) $aluno->id);
            $context['aluno_items'] = $items;
            unset($context['selected_aluno_item'], $context['selected_aluno_item_can_confirm']);
            $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

            return $this->buildAlunoInscricoesListMessage($items);
        }

        if ($option === 3) {
            return $this->respondWithMenu($conversation, false);
        }

        return $this->buildAlunoMenuMessage($context['aluno_snapshot'], 'Opção inválida. Escolha 1, 2 ou 3.');
    }

    private function handleAlunoViewDataInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            $context['aluno_mode'] = 'update';
            $context['aluno_origin_state'] = BotState::ALUNO_VIEW_DATA;
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context);
        }

        if ($option === 2) {
            $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

            return $this->buildAlunoMenuMessage($context['aluno_snapshot'] ?? []);
        }

        return $this->buildAlunoDataViewMessage($context['aluno_snapshot'] ?? [], 'Opção inválida. Escolha 1 ou 2.');
    }

    private function handleAlunoEditFieldInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $fields = $context['edit_fields'] ?? [];
        $index = (int) ($context['edit_index'] ?? 0);
        $mode = (string) ($context['aluno_mode'] ?? 'update');

        if (! is_array($fields) || $fields === []) {
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context);
        }

        if (trim($text) === '3') {
            $context = $this->clearAlunoProfileEditContext($context);

            if ($mode === 'create') {
                $backState = (string) ($context['wizard_back_state'] ?? BotState::ALUNO_CPF);
                if ($backState === BotState::CURSO_CPF) {
                    $this->setConversationState($conversation, BotState::CURSO_CPF, $context);

                    return 'Para se inscrever pelo WhatsApp, informe seu CPF (somente números).';
                }
                if ($backState === BotState::TARGET_OTHER_NOT_FOUND) {
                    $this->setConversationState($conversation, BotState::TARGET_OTHER_NOT_FOUND, $context);

                    return $this->buildOptionsMessage(
                        ['Não encontrei cadastro para o CPF informado. O que deseja fazer?'],
                        [
                            '1) Cadastrar esta pessoa agora',
                            '2) Informar outro CPF',
                            '3) Voltar',
                        ],
                        'Responda com 1, 2 ou 3.'
                    );
                }

                $this->setConversationState($conversation, BotState::ALUNO_CPF, $context);

                return 'Informe seu CPF (somente números).';
            }

            $this->setConversationState($conversation, BotState::ALUNO_VIEW_DATA, $context);

            return $this->buildAlunoDataViewMessage($context['aluno_snapshot'] ?? []);
        }

        if ($index < 0 || $index >= count($fields)) {
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context);
        }

        $field = $fields[$index];
        if (! is_array($field) || ! isset($field['key'])) {
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context);
        }

        $validation = $this->validateAlunoEditValue((string) $field['key'], $text);
        if (($validation['ok'] ?? false) !== true) {
            $error = (string) ($validation['message'] ?? 'Valor inválido.');

            return $this->buildAlunoProfileFieldPrompt($context, $error);
        }

        $editValues = $context['edit_values'] ?? [];
        if (! is_array($editValues)) {
            $editValues = [];
        }
        $editValues[(string) $field['key']] = $validation['value'] ?? null;

        $context['edit_values'] = $editValues;
        $context['edit_index'] = $index + 1;

        if ($context['edit_index'] >= count($fields)) {
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_REVIEW, $context);

            return $this->buildAlunoProfileReviewMessage($context);
        }

        $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

        return $this->buildAlunoProfileFieldPrompt($context);
    }

    private function handleAlunoEditReviewInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $mode = (string) ($context['aluno_mode'] ?? 'update');
        $option = $this->parseNumericOption($text);

        if ($option === 1) {
            $payload = $this->sanitizeAlunoUpdatePayload(is_array($context['edit_values'] ?? null) ? $context['edit_values'] : []);

            if ($mode === 'create') {
                $cpf = Cpf::normalize((string) ($context['aluno_cpf'] ?? ''));
                if ($cpf === '' || ! Cpf::isValid($cpf)) {
                    $nextState = (string) ($context['wizard_back_state'] ?? BotState::ALUNO_CPF);
                    if (! in_array($nextState, [BotState::ALUNO_CPF, BotState::CURSO_CPF, BotState::TARGET_OTHER_NOT_FOUND], true)) {
                        $nextState = BotState::ALUNO_CPF;
                    }
                    $this->setConversationState($conversation, $nextState, $this->clearAlunoProfileEditContext($context));

                    return 'CPF inválido. Envie apenas números.';
                }

                $payload['cpf'] = $cpf;

                try {
                    $aluno = $this->alunoService->create($payload);
                } catch (Throwable) {
                    return $this->buildAlunoProfileReviewMessage(
                        $context,
                        'Não foi possível concluir o cadastro. Revise os dados e tente novamente.'
                    );
                }

                $context['aluno_id'] = (int) $aluno->id;
                $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno);
                $wizardReturnState = (string) ($context['wizard_return_state'] ?? '');
                $wizardReturnPayload = $context['wizard_return_payload'] ?? [];
                if (! is_array($wizardReturnPayload)) {
                    $wizardReturnPayload = [];
                }
                $wizardSetTarget = (bool) ($context['wizard_set_target'] ?? false);
                $context = $this->clearAlunoProfileEditContext($context);
                if ($wizardSetTarget) {
                    $context = $this->setTargetAlunoInContext($context, $aluno, 'cpf');
                }
                unset($context['wizard_return_state'], $context['wizard_return_payload'], $context['wizard_set_target'], $context['wizard_back_state']);

                if ($wizardReturnState !== '') {
                    return $this->resumeAfterTargetSelection(
                        $conversation,
                        $context,
                        $wizardReturnState,
                        $wizardReturnPayload,
                        'Cadastro realizado com sucesso.'
                    );
                }

                if (isset($context['selected_evento_id']) || isset($context['selected_event_id'])) {
                    $this->setConversationState($conversation, BotState::CURSO_ALUNO_CONFIRM, $context);

                    return $this->buildAlunoConfirmMessage(
                        $context['aluno_snapshot'],
                        'Cadastro realizado com sucesso. Agora confirme para concluir sua inscrição.'
                    );
                }

                $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

                return $this->buildAlunoMenuMessage($context['aluno_snapshot'], 'Cadastro realizado com sucesso.');
            }

            $aluno = $this->getSelectedAlunoFromContext($context);
            if (! $aluno) {
                return $this->askAlunoCpf($conversation);
            }

            try {
                $aluno = $this->alunoService->update($aluno, $payload);
            } catch (Throwable) {
                return $this->buildAlunoProfileReviewMessage(
                    $context,
                    'Não foi possível atualizar seus dados. Revise as informações.'
                );
            }

            $context['aluno_snapshot'] = $this->buildAlunoSnapshot($aluno->refresh());
            $originState = (string) ($context['aluno_origin_state'] ?? BotState::ALUNO_VIEW_DATA);
            $context = $this->clearAlunoProfileEditContext($context);
            $this->setConversationState($conversation, BotState::ALUNO_VIEW_DATA, $context);

            if ($originState === BotState::ALUNO_MENU) {
                $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

                return $this->buildAlunoMenuMessage($context['aluno_snapshot'], 'Dados atualizados com sucesso.');
            }

            return $this->buildAlunoDataViewMessage($context['aluno_snapshot'], 'Dados atualizados com sucesso.');
        }

        if ($option === 2) {
            $context = $this->startAlunoProfileWizard($context);
            $this->setConversationState($conversation, BotState::ALUNO_EDIT_FIELD, $context);

            return $this->buildAlunoProfileFieldPrompt($context);
        }

        if ($option === 3) {
            $context = $this->clearAlunoProfileEditContext($context);
            $mode = (string) ($context['aluno_mode'] ?? 'update');

            if ($mode === 'create') {
                $backState = (string) ($context['wizard_back_state'] ?? BotState::ALUNO_CPF);
                if ($backState === BotState::CURSO_CPF) {
                    $this->setConversationState($conversation, BotState::CURSO_CPF, $context);

                    return 'Para se inscrever pelo WhatsApp, informe seu CPF (somente números).';
                }
                if ($backState === BotState::TARGET_OTHER_NOT_FOUND) {
                    $this->setConversationState($conversation, BotState::TARGET_OTHER_NOT_FOUND, $context);

                    return $this->buildOptionsMessage(
                        ['Não encontrei cadastro para o CPF informado. O que deseja fazer?'],
                        [
                            '1) Cadastrar esta pessoa agora',
                            '2) Informar outro CPF',
                            '3) Voltar',
                        ],
                        'Responda com 1, 2 ou 3.'
                    );
                }

                $this->setConversationState($conversation, BotState::ALUNO_CPF, $context);

                return 'Informe seu CPF (somente números).';
            }

            $this->setConversationState($conversation, BotState::ALUNO_VIEW_DATA, $context);

            return $this->buildAlunoDataViewMessage($context['aluno_snapshot'] ?? []);
        }

        return $this->buildAlunoProfileReviewMessage($context, 'Opção inválida. Escolha 1, 2 ou 3.');
    }

    private function handleAlunoInscricoesListInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $items = $context['aluno_items'] ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        $option = $this->parseNumericOption($text);
        if ($items === []) {
            if ($option === 1) {
                $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

                return $this->buildAlunoMenuMessage($context['aluno_snapshot'] ?? []);
            }

            return $this->buildAlunoInscricoesListMessage([], 'Opção inválida. Escolha 1 para voltar.');
        }

        if ($option === 0) {
            $this->setConversationState($conversation, BotState::ALUNO_MENU, $context);

            return $this->buildAlunoMenuMessage($context['aluno_snapshot'] ?? []);
        }

        if ($option === null || $option < 1 || $option > count($items)) {
            return $this->buildAlunoInscricoesListMessage($items, 'Opção inválida. Escolha um item da lista.');
        }

        $selectedItem = $items[$option - 1] ?? null;
        if (! is_array($selectedItem)) {
            return $this->buildAlunoInscricoesListMessage($items, 'A inscrição selecionada não está mais disponível.');
        }

        $context['selected_aluno_item'] = $selectedItem;
        $context['selected_aluno_item_can_confirm'] = $this->isAlunoItemConfirmAvailable($selectedItem);
        $this->setConversationState($conversation, BotState::ALUNO_INSCRICAO_ACTION, $context);

        return $this->buildAlunoInscricaoActionMessage($selectedItem, (bool) $context['selected_aluno_item_can_confirm']);
    }

    private function handleAlunoInscricaoActionInput(BotConversation $conversation, string $text): string
    {
        $context = $this->getConversationContext($conversation);
        $selectedItem = $context['selected_aluno_item'] ?? null;

        if (! is_array($selectedItem)) {
            $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

            return $this->buildAlunoInscricoesListMessage(is_array($context['aluno_items'] ?? null) ? $context['aluno_items'] : []);
        }

        $canConfirm = (bool) ($context['selected_aluno_item_can_confirm'] ?? false);
        $option = $this->parseNumericOption($text);

        if ($canConfirm) {
            if ($option === 1) {
                $message = $this->confirmarItemSelecionadoNoFluxoAluno($selectedItem);
                $aluno = $this->getSelectedAlunoFromContext($context);
                $context['aluno_items'] = $aluno ? $this->buscarItensParaCancelamento((int) $aluno->id) : [];
                $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

                return $this->buildAlunoInscricoesListMessage($context['aluno_items'], $message);
            }

            if ($option === 2) {
                $message = $this->cancelarItemSelecionadoMensagem($selectedItem);
                $aluno = $this->getSelectedAlunoFromContext($context);
                $context['aluno_items'] = $aluno ? $this->buscarItensParaCancelamento((int) $aluno->id) : [];
                $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

                return $this->buildAlunoInscricoesListMessage($context['aluno_items'], $message);
            }

            if ($option === 3) {
                $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

                return $this->buildAlunoInscricoesListMessage(is_array($context['aluno_items'] ?? null) ? $context['aluno_items'] : []);
            }

            return $this->buildAlunoInscricaoActionMessage($selectedItem, true, 'Opção inválida. Escolha 1, 2 ou 3.');
        }

        if ($option === 1) {
            $message = $this->cancelarItemSelecionadoMensagem($selectedItem);
            $aluno = $this->getSelectedAlunoFromContext($context);
            $context['aluno_items'] = $aluno ? $this->buscarItensParaCancelamento((int) $aluno->id) : [];
            $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

            return $this->buildAlunoInscricoesListMessage($context['aluno_items'], $message);
        }

        if ($option === 2) {
            $this->setConversationState($conversation, BotState::ALUNO_INSCRICOES_LIST, $context);

            return $this->buildAlunoInscricoesListMessage(is_array($context['aluno_items'] ?? null) ? $context['aluno_items'] : []);
        }

        return $this->buildAlunoInscricaoActionMessage($selectedItem, false, 'Opção inválida. Escolha 1 ou 2.');
    }

    /**
     * @param array<string, string> $snapshot
     */
    private function buildAlunoMenuMessage(array $snapshot, ?string $prefix = null): string
    {
        $header = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $header[] = trim($prefix);
            $header[] = '';
        }

        $header[] = 'Menu do aluno:';
        $header[] = 'Aluno: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado');

        return $this->buildOptionsMessage(
            $header,
            [
                '1) Ver meus dados',
                '2) Ver minhas inscrições',
                '3) Voltar ao menu',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @param array<string, string> $snapshot
     */
    private function buildAlunoDataViewMessage(array $snapshot, ?string $prefix = null): string
    {
        $header = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $header[] = trim($prefix);
            $header[] = '';
        }

        $header[] = 'Seus dados:';
        $header[] = 'Nome: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado');
        $header[] = 'CPF: ' . (($snapshot['cpf'] ?? '') ?: 'Não informado');
        $header[] = 'Telefone: ' . (($snapshot['contato'] ?? '') ?: 'Não informado');
        $header[] = 'E-mail: ' . (($snapshot['email'] ?? '') ?: 'Não informado');
        $header[] = 'Data de nascimento: ' . (($snapshot['data_nascimento'] ?? '') ?: 'Não informado');
        $header[] = 'Sexo: ' . (($snapshot['sexo_label'] ?? '') ?: 'Não informado');

        return $this->buildOptionsMessage(
            $header,
            [
                '1) Editar meus dados',
                '2) Voltar',
            ],
            'Responda com 1 ou 2.'
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildAlunoProfileFieldPrompt(array $context, ?string $prefix = null): string
    {
        $fields = $context['edit_fields'] ?? [];
        $index = (int) ($context['edit_index'] ?? 0);
        $prompt = 'Informe o valor do campo:';

        if (is_array($fields) && isset($fields[$index]) && is_array($fields[$index]) && isset($fields[$index]['prompt'])) {
            $prompt = (string) $fields[$index]['prompt'];
        }

        $lines = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $lines[] = trim($prefix);
        }
        $lines[] = $prompt;
        $lines[] = 'Digite 3 para voltar.';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildAlunoProfileReviewMessage(array $context, ?string $prefix = null): string
    {
        $mode = (string) ($context['aluno_mode'] ?? 'update');
        $snapshot = $context['aluno_snapshot'] ?? [];
        if (! is_array($snapshot)) {
            $snapshot = [];
        }

        $editValues = $context['edit_values'] ?? [];
        if (! is_array($editValues)) {
            $editValues = [];
        }

        foreach ($editValues as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $scalarValue = $value === null ? '' : (string) $value;
            if (in_array($key, ['celular', 'telefone'], true)) {
                $snapshot[$key] = Phone::format($scalarValue);
                $snapshot['contato'] = Phone::format($scalarValue);

                continue;
            }

            if ($key === 'data_nascimento') {
                $snapshot[$key] = $this->formatDataNascimentoForDisplay($scalarValue);

                continue;
            }

            if ($key === 'sexo') {
                $snapshot[$key] = $scalarValue;
                $snapshot['sexo_label'] = $this->getSexoLabel($scalarValue);

                continue;
            }

            $snapshot[$key] = $scalarValue;
        }

        if ($mode === 'create' && ! isset($snapshot['cpf'])) {
            $snapshot['cpf'] = Cpf::format((string) ($context['aluno_cpf'] ?? ''));
        }

        $headerLines = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $headerLines[] = trim($prefix);
            $headerLines[] = '';
        }

        $headerLines[] = 'Confira os dados informados:';
        $headerLines[] = 'Nome: ' . (($snapshot['nome_completo'] ?? '') ?: 'Não informado');
        $headerLines[] = 'CPF: ' . (($snapshot['cpf'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Telefone: ' . (($snapshot['contato'] ?? '') ?: 'Não informado');
        $headerLines[] = 'E-mail: ' . (($snapshot['email'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Data de nascimento: ' . (($snapshot['data_nascimento'] ?? '') ?: 'Não informado');
        $headerLines[] = 'Sexo: ' . (($snapshot['sexo_label'] ?? '') ?: 'Não informado');

        return $this->buildOptionsMessage(
            $headerLines,
            [
                '1) ' . ($mode === 'create' ? 'Confirmar cadastro' : 'Confirmar atualização'),
                '2) Refazer correção',
                '3) Voltar',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @param list<array{type: string, id: int, curso_id: int, curso_nome: string, periodo: string, status: string}> $items
     */
    private function buildAlunoInscricoesListMessage(array $items, ?string $prefix = null): string
    {
        if ($items === []) {
            return $this->buildOptionsMessage(
                array_values(array_filter([
                    $prefix,
                    $prefix !== null && trim($prefix) !== '' ? '' : null,
                    'Você não possui inscrições ativas no momento.',
                ], static fn ($line) => $line !== null)),
                ['1) Voltar'],
                'Responda com 1.'
            );
        }

        $lines = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $lines[] = trim($prefix);
            $lines[] = '';
        }
        $lines[] = 'Suas inscrições ativas:';
        $lines = [...$lines, ...$this->buildAlunoInscricaoItemLines($items)];
        $lines[] = '0️⃣ Voltar';
        $lines[] = '';
        $lines[] = 'Digite o número da inscrição para ver as ações.';
        $lines[] = 'Responda com o número da inscrição ou 0.';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildAlunoInscricaoActionMessage(array $item, bool $canConfirm, ?string $prefix = null): string
    {
        $header = [];
        if ($prefix !== null && trim($prefix) !== '') {
            $header[] = trim($prefix);
            $header[] = '';
        }

        $typeLabel = ($item['type'] ?? '') === 'matricula' ? 'Matrícula' : 'Inscrição';
        $header[] = 'Detalhes da inscrição:';
        $header[] = 'Tipo: ' . $typeLabel;
        $header[] = 'Curso: ' . ($item['curso_nome'] ?? 'Curso');
        $header[] = 'Período: ' . ($item['periodo'] ?? 'Data não informada');
        $header[] = 'Status: ' . ($item['status'] ?? 'Não informado');

        if ($canConfirm) {
            return $this->buildOptionsMessage(
                $header,
                [
                    '1) Confirmar inscrição',
                    '2) Cancelar inscrição',
                    '3) Voltar',
                ],
                'Responda com 1, 2 ou 3.'
            );
        }

        return $this->buildOptionsMessage(
            $header,
            [
                '1) Cancelar inscrição',
                '2) Voltar',
            ],
            'Responda com 1 ou 2.'
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function startAlunoProfileWizard(array $context): array
    {
        $context['edit_fields'] = $this->getAlunoEditFields();
        $context['edit_index'] = 0;
        $context['edit_values'] = [];

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function clearAlunoProfileEditContext(array $context): array
    {
        unset(
            $context['edit_fields'],
            $context['edit_index'],
            $context['edit_values'],
            $context['aluno_mode'],
            $context['aluno_origin_state']
        );

        return $context;
    }

    /**
     * @param list<array{type: string, id: int, curso_id: int, curso_nome: string, periodo: string, status: string}> $items
     * @return list<string>
     */
    private function buildAlunoInscricaoItemLines(array $items): array
    {
        $lines = [];

        foreach (array_values($items) as $index => $item) {
            $typeLabel = ($item['type'] ?? '') === 'matricula' ? 'Matrícula' : 'Inscrição';
            $lines[] = ($index + 1) . ') '
                . ($item['curso_nome'] ?? 'Curso')
                . ' - '
                . $typeLabel
                . ' ('
                . ($item['status'] ?? 'Não informado')
                . ')'
                . ' - '
                . ($item['periodo'] ?? 'Data não informada');
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isAlunoItemConfirmAvailable(array $item): bool
    {
        if (($item['type'] ?? '') !== 'matricula') {
            return false;
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $matricula = Matricula::query()->find($id);
        if (! $matricula) {
            return false;
        }

        return $matricula->status === StatusMatricula::Pendente;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function confirmarItemSelecionadoNoFluxoAluno(array $item): string
    {
        if (($item['type'] ?? '') !== 'matricula') {
            return 'Confirmação não disponível para este item.';
        }

        $id = (int) ($item['id'] ?? 0);
        if ($id <= 0) {
            return 'A inscrição selecionada não foi encontrada.';
        }

        $matricula = Matricula::query()
            ->with('eventoCurso.curso')
            ->find($id);

        if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
            return 'A matrícula selecionada não foi encontrada.';
        }

        try {
            $this->matriculaService->confirmarMatricula($matricula);
        } catch (Throwable) {
            return 'Não foi possível confirmar a inscrição no momento.';
        }

        return 'Inscrição confirmada com sucesso.';
    }

    private function askCancelCpf(BotConversation $conversation): string
    {
        $context = $this->getConversationContext($conversation);
        $target = $this->getTargetAlunoFromContext($context);
        if ($target) {
            $context = $this->setTargetAlunoInContext($context, $target, (string) ($context['target_source'] ?? 'cpf'));

            return $this->resumeAfterTargetSelection($conversation, $context, BotState::CANCEL_LIST, []);
        }

        $alunoPhone = $this->findAlunoByPhone((string) ($conversation->from ?? ''));
        if ($alunoPhone) {
            return $this->startTargetPick($conversation, $context, $alunoPhone, BotState::CANCEL_LIST);
        }

        $this->setConversationState($conversation, BotState::CANCEL_CPF, $context);

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

        $context = $this->setTargetAlunoInContext($this->getConversationContext($conversation), $aluno, 'cpf');

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
                'target_aluno_id' => $context['target_aluno_id'] ?? null,
                'target_cpf' => $context['target_cpf'] ?? null,
                'target_source' => $context['target_source'] ?? 'cpf',
            ]
        );

        return implode("\n", [
            'Inscrições encontradas:',
            ...$lines,
            '0️⃣ Voltar',
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
        if ($option === 0) {
            return $this->respondWithMenu($conversation, false);
        }

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

        return $this->buildOptionsMessage(
            [
                'Confirmar cancelamento?',
                'Tipo: ' . $typeLabel,
                'Curso: ' . ($selectedItem['curso_nome'] ?? 'Curso'),
                'Período: ' . ($selectedItem['periodo'] ?? 'Data não informada'),
            ],
            [
                '1) Confirmar cancelamento',
                '2) Voltar',
            ],
            'Responda com 1 ou 2.'
        );
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

        if (in_array($normalized, ['2', 'voltar', 'v'], true)) {
            $context = $this->getConversationContext($conversation);
            unset($context['selected_item']);
            $this->setConversationState($conversation, BotState::CANCEL_LIST, $context);

            return $this->renderCancelListWithPrefix($conversation, 'Cancelamento não confirmado.');
        }

        return 'Resposta inválida. Digite 1 para confirmar ou 2 para voltar.';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function cancelarItemSelecionado(BotConversation $conversation, array $item): string
    {
        return $this->respondWithMenu($conversation, false, $this->cancelarItemSelecionadoMensagem($item));
    }

    /**
     * @param array<string, mixed> $item
     */
    private function cancelarItemSelecionadoMensagem(array $item): string
    {
        $type = (string) ($item['type'] ?? '');
        $id = (int) ($item['id'] ?? 0);

        if ($id <= 0 || ! in_array($type, ['matricula', 'inscricao'], true)) {
            return 'A inscrição selecionada não foi encontrada.';
        }

        if ($type === 'matricula') {
            $matricula = Matricula::query()
                ->with(['eventoCurso.curso', 'aluno'])
                ->find($id);

            if (! $matricula || ! $matricula->eventoCurso || ! $matricula->eventoCurso->curso) {
                return 'A matrícula selecionada não foi encontrada.';
            }

            if ($matricula->status === StatusMatricula::Cancelada) {
                return 'Esta matrícula já foi cancelada.';
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

            return 'Matrícula cancelada com sucesso.';
        }

        $inscricao = ListaEspera::query()
            ->with('eventoCurso.curso')
            ->find($id);

        if (! $inscricao || ! $inscricao->eventoCurso || ! $inscricao->eventoCurso->curso) {
            return 'A inscrição selecionada não foi encontrada.';
        }

        $this->matriculaService->removerDaListaEspera($inscricao);

        return 'Inscrição cancelada com sucesso.';
    }

    private function respondWithMenu(BotConversation $conversation, bool $includeWelcome, ?string $prefix = null): string
    {
        // Keep current context and let setConversationState merge updates by default.
        $context = $this->getConversationContext($conversation);
        $this->setConversationState($conversation, BotState::MENU, $context);

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
        return $this->buildOptionsMessage(
            ['Escolha uma opção:'],
            [
                '1) Cursos Disponíveis',
                '2) Consultar Aluno',
                '3) Cancelar Inscrição',
            ],
            'Responda com 1, 2 ou 3.'
        );
    }

    /**
     * @param list<string> $headerLines
     * @param list<string> $options
     */
    private function buildOptionsMessage(array $headerLines, array $options, string $footerLine): string
    {
        $lines = [];

        foreach ($headerLines as $line) {
            $lines[] = $line;
        }

        foreach ($options as $option) {
            $lines[] = $this->formatOptionWithEmoji($option);
        }

        $lines[] = '';
        $lines[] = $footerLine;

        return implode("\n", $lines);
    }

    private function formatOptionWithEmoji(string $option): string
    {
        if (preg_match('/^\s*(\d+)\)\s*(.+)$/u', $option, $matches) !== 1) {
            return trim($option);
        }

        $number = (int) ($matches[1] ?? 0);
        $label = trim((string) ($matches[2] ?? ''));

        return $this->toEmojiNumber($number) . ' ' . $label;
    }

    private function toEmojiNumber(int $number): string
    {
        return match ($number) {
            1 => '1️⃣',
            2 => '2️⃣',
            3 => '3️⃣',
            4 => '4️⃣',
            5 => '5️⃣',
            6 => '6️⃣',
            7 => '7️⃣',
            8 => '8️⃣',
            9 => '9️⃣',
            10 => '🔟',
            default => $number . ')',
        };
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
            '0️⃣ Voltar',
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

    private function reopenConversation(BotConversation $conversation): void
    {
        $context = $this->getConversationContext($conversation);
        // Preserve only the reply JID so WAHA can still reach the user.
        $preserved = [];
        if (isset($context['reply_chat_id']) && trim((string) $context['reply_chat_id']) !== '') {
            $preserved['reply_chat_id'] = $context['reply_chat_id'];
        }

        $this->setConversationState($conversation, BotState::MENU, $preserved);
    }

    private function setConversationState(
        BotConversation $conversation,
        string $state,
        array $context,
        bool $clearContext = false
    ): void
    {
        $resolvedContext = $this->preserveConversationContext($conversation, $context, $clearContext);

        $payload = [
            'state' => $state,
            'context' => $resolvedContext,
            'last_activity_at' => now(),
        ];

        if ($this->hasConversationIsOpenColumn()) {
            $payload['is_open'] = $state !== BotState::ENDED;
        }

        if ($this->hasConversationClosedAtColumn()) {
            $payload['closed_at'] = $state === BotState::ENDED
                ? ($conversation->closed_at ?? now())
                : null;
        }

        if ($this->hasConversationClosedReasonColumn()) {
            $payload['closed_reason'] = $state === BotState::ENDED
                ? ($conversation->closed_reason ?? 'manual')
                : null;
        }

        $this->logConversationContextAudit(
            $conversation,
            'bot_engine.set_conversation_state',
            $state,
            isset($payload['closed_reason']) ? (string) $payload['closed_reason'] : null,
            $conversation->context,
            $resolvedContext
        );

        $conversation->updateWithContextPolicy($payload, $clearContext, __METHOD__);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConversationContext(BotConversation $conversation): array
    {
        return $this->normalizeConversationContext($conversation->context);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeConversationContext(mixed $context): array
    {
        if (is_array($context)) {
            return $context;
        }

        if ($context === null) {
            return [];
        }

        if (! is_string($context)) {
            return [];
        }

        $value = trim($context);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $nextContext
     * @return array<string, mixed>
     */
    private function preserveConversationContext(
        BotConversation $conversation,
        mixed $nextContext,
        bool $clearContext = false
    ): array {
        $normalizedNextContext = $this->normalizeConversationContext($nextContext);
        if ($clearContext) {
            return $normalizedNextContext;
        }

        $existingContext = $this->normalizeConversationContext($conversation->context);
        if ($normalizedNextContext === []) {
            return $existingContext;
        }

        return array_replace($existingContext, $normalizedNextContext);
    }

    /**
     * @param mixed $previousContext
     * @param array<string, mixed> $nextContext
     */
    private function logConversationContextAudit(
        BotConversation $conversation,
        string $origin,
        string $nextState,
        ?string $nextClosedReason,
        mixed $previousContext,
        array $nextContext
    ): void {
        if (! $this->shouldLogContextAudit()) {
            return;
        }

        $payload = [
            'conversation_id' => (int) $conversation->id,
            'origin' => $origin,
            'previous_state' => (string) ($conversation->state ?? ''),
            'next_state' => $nextState,
            'previous_closed_reason' => $conversation->closed_reason,
            'next_closed_reason' => $nextClosedReason,
            'previous_context_type' => gettype($previousContext),
            'previous_context_summary' => $this->summarizeConversationContextForAudit(
                $this->normalizeConversationContext($previousContext)
            ),
            'next_context_summary' => $this->summarizeConversationContextForAudit($nextContext),
        ];

        if ($this->shouldIncludeContextAuditTrace()) {
            $payload['stack'] = $this->buildContextAuditStackTrace();
        }

        Log::info('BOT context audit', $payload);
    }

    private function shouldLogContextAudit(): bool
    {
        return app()->environment(['local', 'testing'])
            || (bool) $this->configuracaoService->get('bot.audit_context_updates', false);
    }

    private function shouldIncludeContextAuditTrace(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    /**
     * @return list<string>
     */
    private function buildContextAuditStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $frames = [];

        foreach (array_slice($trace, 1, 5) as $frame) {
            $class = (string) ($frame['class'] ?? '');
            $type = (string) ($frame['type'] ?? '');
            $function = (string) ($frame['function'] ?? '');
            $file = isset($frame['file']) ? basename((string) $frame['file']) : 'unknown';
            $line = isset($frame['line']) ? (int) $frame['line'] : 0;

            $frames[] = trim($class . $type . $function, ':') . '@' . $file . ':' . $line;
        }

        return $frames;
    }

    /**
     * @param array<string, mixed> $context
     * @return array{
     *   keys: list<string>,
     *   size: int,
     *   has_reply_chat_id: bool,
     *   reply_chat_id_masked: ?string
     * }
     */
    private function summarizeConversationContextForAudit(array $context): array
    {
        $replyChatId = trim((string) ($context['reply_chat_id'] ?? ''));

        return [
            'keys' => array_values(array_map(
                static fn ($key): string => (string) $key,
                array_keys($context)
            )),
            'size' => count($context),
            'has_reply_chat_id' => $replyChatId !== '',
            'reply_chat_id_masked' => $replyChatId !== '' ? $this->maskIdentifierForAudit($replyChatId) : null,
        ];
    }

    private function maskIdentifierForAudit(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '***';
        }

        $local = explode('@', $clean)[0] ?? $clean;
        $digits = preg_replace('/\D+/', '', $local) ?? '';
        if ($digits !== '') {
            return '***' . substr($digits, -4);
        }

        if (mb_strlen($local) <= 4) {
            return '***';
        }

        return mb_substr($local, 0, 2) . '***' . mb_substr($local, -2);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getSelectedEventoFromContext(array $context): ?EventoCurso
    {
        $eventoId = (int) ($context['selected_evento_id'] ?? 0);
        if ($eventoId <= 0) {
            $eventoId = (int) ($context['selected_event_id'] ?? 0);
        }
        if ($eventoId <= 0) {
            return null;
        }

        $eventoQuery = EventoCurso::query()
            ->with('curso')
            ->where('id', $eventoId)
            ->where('ativo', true)
            ->whereHas('curso', fn ($query) => $query->where('ativo', true));

        $this->applyActiveCourseDateFilter($eventoQuery);

        return $eventoQuery->first();
    }

    private function applyActiveCourseDateFilter(Builder $query): void
    {
        $today = $this->getTodayDateInAppTimezone();

        $query->where(function (Builder $builder) use ($today) {
            $builder->whereDate('data_fim', '>=', $today)
                ->orWhere(function (Builder $fallbackBuilder) use ($today) {
                    $fallbackBuilder->whereNull('data_fim')
                        ->whereDate('data_inicio', '>=', $today);
                });
        });
    }

    private function getTodayDateInAppTimezone(): string
    {
        return CarbonImmutable::now((string) config('app.timezone'))->toDateString();
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
            'Atendimento encerrado. Quando precisar, digite *menu* para começar novamente.'
        ));

        if ($message === '') {
            return 'Atendimento encerrado. Quando precisar, digite *menu* para começar novamente.';
        }

        return $message;
    }

    private function getWelcomeMessage(): string
    {
        $message = trim((string) $this->configuracaoService->get(
            'bot.welcome_message',
            "🏛️ Sindicato Rural de Miranda e Bodoquena\nSeja bem-vindo(a) ao atendimento automático do Sindicato."
        ));

        if ($message === '') {
            return "🏛️ Sindicato Rural de Miranda e Bodoquena\nSeja bem-vindo(a) ao atendimento automático do Sindicato.";
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
        $context = $this->getConversationContext($conversation);
        $preserved = [];
        if (isset($context['reply_chat_id']) && trim((string) $context['reply_chat_id']) !== '') {
            $preserved['reply_chat_id'] = $context['reply_chat_id'];
        }

        $this->setConversationState($conversation, BotState::ENDED, $preserved);

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

    private function hasConversationClosedAtColumn(): bool
    {
        if ($this->hasConversationClosedAtColumn !== null) {
            return $this->hasConversationClosedAtColumn;
        }

        $this->hasConversationClosedAtColumn = Schema::hasColumn('bot_conversations', 'closed_at');

        return $this->hasConversationClosedAtColumn;
    }

    private function hasConversationClosedReasonColumn(): bool
    {
        if ($this->hasConversationClosedReasonColumn !== null) {
            return $this->hasConversationClosedReasonColumn;
        }

        $this->hasConversationClosedReasonColumn = Schema::hasColumn('bot_conversations', 'closed_reason');

        return $this->hasConversationClosedReasonColumn;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = mb_strtolower(trim($channel));
        $supported = $this->providerFactory->supportedChannels();

        if ($supported === []) {
            return 'meta';
        }

        if (in_array($channel, $supported, true)) {
            return $channel;
        }

        $configured = mb_strtolower(trim((string) $this->configuracaoService->get('bot.provider', 'meta')));
        if (in_array($configured, $supported, true)) {
            return $configured;
        }

        return $supported[0];
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
