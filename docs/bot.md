# Módulo BOT (WhatsApp)

> Nota: para detalhes atuais do multi-provedor (`meta`, `zapi`, `waha`, `evolution`), consulte `docs/whatsapp.md`.

## Visão geral

O módulo BOT oferece atendimento automático via WhatsApp com menu de opções para:

- 1️⃣ Cursos Disponíveis
- 2️⃣ Consultar Aluno
- 3️⃣ Cancelar Inscrição

Provedores suportados:

- Meta Cloud API
- Z-API

As conversas usam estado/contexto persistidos em `bot_conversations` e, quando habilitado, logs em `bot_message_logs`.

## Fluxos principais

### 1) Menu inicial

Qualquer mensagem de entrada (ou palavra-chave de entrada) abre o menu com as 3 opções.

### 2) Cursos Disponíveis

- Lista cursos/eventos disponíveis com período, horário e vagas.
- O usuário seleciona um item para ver resumo.
- Fluxo de inscrição segue pelo WhatsApp.
- Identificação do aluno:
  - tenta vínculo pelo telefone (TARGET_PICK: atender esta pessoa / outra pessoa / voltar);
  - se necessário, permite informar CPF.
- Antes da inscrição, mostra dados do aluno e pede confirmação:
  - confirmar inscrição;
  - corrigir informações;
  - voltar.

### 3) Consultar Aluno

- Tenta identificar por telefone (TARGET_PICK) ou CPF.
- Se CPF não existir, oferece cadastro no próprio bot (wizard).
- Menu do aluno:
  - ver/editar dados;
  - ver inscrições;
  - voltar ao menu principal.

### 4) Cancelar Inscrição

- Identificação por telefone (TARGET_PICK) ou CPF.
- Lista itens elegíveis (matrículas/inscrições).
- Solicita confirmação para efetivar cancelamento conforme regra existente do sistema.

### 5) Atender outra pessoa

Quando existe cadastro vinculado ao telefone, o bot oferece:

- 1️⃣ Atender esta pessoa
- 2️⃣ Atender outra pessoa (CPF)
- 3️⃣ Voltar

Se CPF não existir, oferece cadastro via wizard e retorna ao fluxo original após salvar.

### 6) Encerramento da conversa

- Palavras-chave de saída configuráveis (`bot_exit_keywords`) encerram em qualquer estado.
- Mensagem de encerramento configurável (`bot_close_message`).
- Encerramento por inatividade via comando agendado `bot:close-inactive`.

## Configurações no Admin

Caminho: **Configurações → Bot**

Principais settings:

- `bot_enabled`
- `bot_provider` (`meta|zapi`)
- `bot_session_timeout_minutes`
- `bot_reset_keyword`
- `bot_entry_keywords`
- `bot_exit_keywords`
- `bot_welcome_message`
- `bot_fallback_message`
- `bot_close_message`
- `bot_inactive_close_message`
- `bot_audit_log_enabled`

Configurações de credenciais do bot:

- `bot_credentials_mode`
  - `inherit_notifications`: herda credenciais de `whatsapp.*` (mesmo comportamento das Notificações)
  - `custom`: usa credenciais próprias do bot

Credenciais custom por provedor:

- Meta:
  - `bot_meta_phone_number_id`
  - `bot_meta_access_token`
- Z-API:
  - `bot_zapi_instance_id`
  - `bot_zapi_token`
  - `bot_zapi_client_token`
  - `bot_zapi_base_url`

Observação de UI:

- Em `custom`, a tela exibe apenas os campos do provedor selecionado (`meta` ou `zapi`).
- Em `inherit_notifications`, o bloco de credenciais custom fica oculto.

## Webhooks

Rotas:

- `POST /webhooks/bot/meta`
- `POST /webhooks/bot/zapi`

Regra de provedor ativo:

- Se `bot_provider=meta`, apenas webhook Meta processa; Z-API responde `ignored`.
- Se `bot_provider=zapi`, apenas webhook Z-API processa; Meta responde `ignored`.

Recomendações de segurança:

- Proteger endpoint com assinatura/token (header ou segredo na URL, conforme infraestrutura).
- Aplicar rate limit no gateway/reverse proxy.
- Não registrar tokens em logs.

## Scheduler/Cron

Em produção, é obrigatório rodar:

- `php artisan schedule:run` a cada minuto (cron do servidor).

Jobs relevantes ao BOT:

- `bot:close-inactive` (minutely)
- `confirmacoes:enviar` (diário, no horário configurado em `app.scheduler.lembrete_horario`)
- `vagas-disponiveis:enviar` (hourly)

## Eventos expirados e disponibilidade de cursos

Comando de encerramento:

- `php artisan eventos:encerrar-expirados`

Regra de expiração:

- encerra (`ativo=false`) somente quando `data_fim < hoje` (timezone da aplicação);
- `data_fim = hoje` ainda permanece ativo (expira apenas no dia seguinte).

Filtro de disponibilidade por data aplicado em:

- `BotEngine::listCourses` e consultas auxiliares de seleção/retomada;
- `PublicController::cursos`;
- `AlunoAreaController::inscricoes`.

Impacto:

- eventos expirados deixam de aparecer como disponíveis;
- rotinas que dependem de `evento_cursos.ativo=true` param naturalmente para eventos encerrados.

## Atualizações recentes (2026-03-08)

### Reinscrição após cancelamento (índice único em `matriculas`)

Foi formalizado o comportamento de reinscrição para o mesmo par `aluno_id + evento_curso_id`:

- A tabela `matriculas` possui índice único (`aluno_id`, `evento_curso_id`).
- Se a matrícula anterior estiver `cancelada` ou `expirada`, a nova inscrição via WhatsApp não cria outro registro.
- O sistema reutiliza o mesmo registro (mesmo `id`) e altera o status para `pendente`.

Exemplo prático:

1. Aluno cancela a matrícula no evento.
2. Aluno reinscreve no mesmo evento pelo BOT.
3. O registro original é reativado para `pendente` (sem novo `id`).

### Contrato interno de status da inscrição (service -> BOT)

`MatriculaService::solicitarInscricao(...)` retorna um status estruturado consumido pelo BOT:

- `created`: inscrição criada ou reativada com sucesso.
- `already_enrolled`: já existe matrícula ativa (`pendente` ou `confirmada`).
- `waitlist`: já existe lista de espera ativa (`aguardando` ou `chamado`).
- `no_vacancies`: sem vagas imediatas; fluxo segue para lista de espera conforme regra.

Resposta esperada no BOT:

- `created`: confirma inscrição concluída com sucesso.
- `already_enrolled`: "Você já possui inscrição neste curso."
- `waitlist`: "Você já está na lista de espera deste curso."
- `no_vacancies`: informa ausência de vaga imediata e inclusão em lista de espera.

### Matriz de bloqueio da inscrição

- Bloqueia inscrição: matrícula `pendente|confirmada`.
- Não bloqueia inscrição: matrícula `cancelada|expirada`.
- Lista de espera ativa (`aguardando|chamado`): não trata como matrícula; retorna mensagem específica de lista de espera.

### Confirmação pós-inscrição (link)

Quando a inscrição fica em `pendente` e a configuração automática está ativa:

- o sistema pode enviar mensagem de confirmação com link (incluindo canal WhatsApp);
- o envio pode ocorrer imediatamente (quando elegível pela regra atual) ou pela rotina agendada `confirmacoes:enviar`.

### Diagnóstico (debug do BOT)

Para investigação em ambiente controlado:

- habilitar `bot.debug=true`;
- observar logs sanitizados:
  - `BOT inscrição diagnóstico`
  - `BOT inscrição falhou`
- o log inclui, entre outros campos, `selected_event_id`, `target_aluno_id`, `status` retornado e resumo de duplicidade (`matricula_encontrada` / `lista_espera_encontrada`), sem expor tokens.

Comando sugerido:

- `rg -n "BOT inscrição" storage/logs/laravel.log`

### Nota de scheduler

- Rotina de vagas (`vagas-disponiveis:enviar`): corrigido uso da variável `$diasAntes` em `MatriculaService` para evitar erro `Undefined variable $diasAntes`.
