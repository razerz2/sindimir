# Módulo BOT (WhatsApp)

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
