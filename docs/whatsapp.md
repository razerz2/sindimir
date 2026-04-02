# Modulo WhatsApp (Multi-Provedor)

## Escopo

Este documento descreve a implementacao real do modulo WhatsApp no projeto, com foco em:

- notificacoes do sistema
- bot de atendimento
- configuracao/admin
- webhooks
- health/status
- testes e homologacao

Provedores suportados no codigo:

- `meta`
- `zapi`
- `waha`
- `evolution`

## Arquitetura

### Componentes centrais

- `App\Services\WhatsAppService`
  - facade de envio para notificacoes e teste de envio
  - nao conhece HTTP de cada provedor
- `App\Services\WhatsApp\WhatsAppProviderResolver`
  - resolve providers a partir de `config('services.whatsapp.provider_registry')`
- `App\Services\WhatsApp\WhatsAppProviderConfigResolver`
  - monta config por provedor a partir de `ConfiguracaoService` + `config/services.php`
- `App\Services\WhatsApp\WhatsAppProviderStatusService`
  - consulta health do provedor ativo ou de um provedor especifico

### Contratos

- `WhatsAppNotificationProviderInterface`
  - `canSend`, `canTestSend`, `send`
- `WhatsAppBotProviderInterface`
  - `sendBotText`
- `WhatsAppHealthProviderInterface`
  - `getHealthStatus`

### Providers concretos

- `MetaWhatsAppProvider`
- `ZApiWhatsAppProvider`
- `WahaWhatsAppProvider`
- `EvolutionWhatsAppProvider`

### Bot

- `BotProviderFactory` resolve o canal dinamicamente via `WhatsAppProviderResolver`
- `ConfiguredBotProvider` injeta `WhatsAppProviderConfig` resolvida para o provider ativo
- `BotEngine` normaliza canal com base em `supportedChannels()` da factory

## Como o provedor ativo e resolvido

### Notificacoes

- chave principal: `whatsapp.provedor`
- o envio chama:
  1. `WhatsAppProviderConfigResolver::resolveNotificationProvider()`
  2. `WhatsAppProviderResolver::resolve(...)`
  3. `provider->send(...)`

### Bot

- chave principal: `bot.provider`
- modo de credenciais:
  - `bot.credentials_mode=inherit_notifications`: usa config de notificacoes do mesmo provider
  - `bot.credentials_mode=custom`: usa chaves `bot.*` especificas por provider

## Endpoints HTTP usados por provider (codigo real)

### Meta

- envio texto: `POST {meta.base_url}/{phone_number_id}/messages`
- health: nao implementa `WhatsAppHealthProviderInterface`

### Z-API

- envio texto: `POST {base_url}/instances/{instance}/token/{token}/send-text`
- envio com link detectado na mensagem: `POST {base_url}/instances/{instance}/token/{token}/send-link`
- health: `GET {base_url}/instances/{instance}/token/{token}/status`
  - ok quando `connected=true` e `state=CONNECTED`

### WAHA

- envio texto: `POST {base_url}/api/sendText`
  - body: `session`, `chatId`, `text`
- health: `GET {base_url}/api/sessions/{session}`
  - ok quando `status=WORKING`

### Evolution

- envio texto: `POST {base_url}/message/sendText/{instance}`
  - body: `number`, `text`
  - header: `apikey`
- health: `GET {base_url}/instance/connectionState/{instance}`
  - ok quando `instance.state=open`

## Fluxo de notificacoes

1. `NotificationService` enfileira `SendWhatsAppNotificationJob`.
2. Job consulta `WhatsAppProviderStatusService::getActiveProviderStatus()`.
3. Se `applies=true` e `can_send=false`, grava log como `failed` (nao envia).
4. Se disponivel, chama `WhatsAppService::send(...)`.
5. `WhatsAppService` delega ao provider ativo resolvido dinamicamente.

## Fluxo do bot

1. Webhook por provider recebe payload.
2. Controller extrai `from` e texto util.
3. Controller chama `BotEngine::handleIncoming(canal, from, text)`.
4. Se houver resposta, usa `BotProviderFactory->make(canal)->sendText(...)`.
5. O envio da resposta passa pelo mesmo provider do canal.

## Configuracao/admin

### Notificacoes (aba WhatsApp)

Campos comuns:

- `whatsapp.provedor`
- `whatsapp.token`
- `whatsapp.client_token`
- `whatsapp.phone_number_id`
- `whatsapp.base_url`
- `whatsapp.instance`
- `whatsapp.webhook_url`

Campos WAHA:

- `whatsapp.waha_base_url`
- `whatsapp.waha_session`
- `whatsapp.waha_api_key`
- `whatsapp.waha_api_key_header`

Campos Evolution:

- `whatsapp.evolution_base_url`
- `whatsapp.evolution_instance`
- `whatsapp.evolution_apikey`

Validacao backend:

- WAHA exige base_url + session quando `whatsapp_provedor=waha`
- Evolution exige base_url + instance + apikey quando `whatsapp_provedor=evolution`

### Bot (aba Bot)

Campos comuns:

- `bot.provider`
- `bot.credentials_mode`

Credenciais custom por provider:

- Meta: `bot.meta_phone_number_id`, `bot.meta_access_token`
- Z-API: `bot.zapi_instance_id`, `bot.zapi_token`, `bot.zapi_client_token`, `bot.zapi_base_url`
- WAHA: `bot.waha_base_url`, `bot.waha_session`, `bot.waha_api_key`, `bot.waha_api_key_header`
- Evolution: `bot.evolution_base_url`, `bot.evolution_instance`, `bot.evolution_apikey`

Validacao backend:

- campos custom sao obrigatorios conforme `bot_provider` quando `bot_credentials_mode=custom`

### Teste de envio no admin

- endpoint interno: `POST /admin/configuracoes/whatsapp/testar`
- usa `WhatsAppService::sendTest(...)`
- respeita provider ativo e validacoes `canTestSend()` de cada provider

## Rotas de webhook por provider

- `POST /webhooks/bot/meta`
- `POST /webhooks/bot/zapi`
- `POST /webhooks/bot/waha`
- `POST /webhooks/bot/evolution`

Regra comum:

- cada webhook so processa quando `bot.enabled=true` e `bot.provider` corresponde ao canal
- caso contrario retorna `{"status":"ignored"}`

## Regras de health/status

- Meta:
  - `applies=false` (sem health provider)
- Z-API:
  - health aplicado quando `services.whatsapp.zapi.enabled=true`
- WAHA:
  - health aplicado quando `services.whatsapp.waha.status_enabled=true` (default true)
- Evolution:
  - health aplicado quando `services.whatsapp.evolution.status_enabled=true` (default true)

Quando health aplicado e desconectado:

- notificacoes via job nao sao enviadas
- motivo fica registrado no log de notificacao

## Troubleshooting por provider

### Meta

- erro de envio: validar `whatsapp.token` e `whatsapp.phone_number_id`
- confirmar `services.whatsapp.meta.base_url`

### Z-API

- erro de envio: validar `whatsapp.base_url`, `whatsapp.instance`, `whatsapp.token`, `whatsapp.client_token`
- health bloqueando envio: validar status da instancia em `/status`

### WAHA

- erro de envio: validar `whatsapp.waha_base_url` e `whatsapp.waha_session`
- se usar chave: validar `whatsapp.waha_api_key` e header configurado
- health bloqueando envio: sessao precisa estar `WORKING`

### Evolution

- erro de envio: validar `whatsapp.evolution_base_url`, `whatsapp.evolution_instance`, `whatsapp.evolution_apikey`
- health bloqueando envio: instancia precisa estar `open`
- conferir header `apikey` sendo aceito pela API

## Testes automatizados

Testes adicionados para este modulo:

- `tests/Unit/WhatsApp/WhatsAppProviderResolverTest.php`
  - cobre registry e resolucao dos 4 providers
- `tests/Unit/WhatsApp/WahaWhatsAppProviderTest.php`
  - cobre endpoint de envio e health WAHA
- `tests/Unit/WhatsApp/EvolutionWhatsAppProviderTest.php`
  - cobre endpoint de envio e health Evolution

Teste de regressao ja existente e mantido:

- `tests/Feature/NotificationTypeRulesTest.php`

Observacao atual do projeto:

- `tests/Feature/ExampleTest.php` falha por tabela `site_sections` ausente no sqlite em memoria
- esta falha e pre-existente e nao pertence ao modulo WhatsApp

## Checklist de homologacao manual

### Envio por provedor

- [ ] Meta: envio manual de mensagem
- [ ] Z-API: envio manual de mensagem
- [ ] WAHA: envio manual de mensagem
- [ ] Evolution: envio manual de mensagem

### Teste de envio (admin)

- [ ] Selecionar cada provedor e usar "Testar envio"
- [ ] Confirmar retorno de sucesso/erro coerente

### Webhook do bot

- [ ] Meta: webhook recebe e processa quando `bot.provider=meta`
- [ ] Z-API: webhook recebe e processa quando `bot.provider=zapi`
- [ ] WAHA: webhook recebe e processa quando `bot.provider=waha`
- [ ] Evolution: webhook recebe e processa quando `bot.provider=evolution`

### Resposta automatica do bot

- [ ] Mensagem de entrada cria/reativa conversa
- [ ] Bot responde menu inicial
- [ ] Palavras de saida encerram sessao

### Validacao de campos

- [ ] WAHA exige base_url + session quando ativo
- [ ] Evolution exige base_url + instance + apikey quando ativo
- [ ] Bot custom exige campos por provider selecionado

### Fallback com provedor desconectado

- [ ] Z-API desconectada bloqueia notificacoes quando health ativo
- [ ] WAHA sem `WORKING` bloqueia notificacoes
- [ ] Evolution sem `open` bloqueia notificacoes
- [ ] motivo de bloqueio aparece no log de notificacoes
