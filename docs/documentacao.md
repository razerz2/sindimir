# Documentacao Unica Sindimir

Documento consolidado com visao geral, configuracao e operacao do sistema.

## Visao geral

Aplicacao Laravel 12 para gerenciamento de cursos, inscricoes e alunos. Ha
area publica (institucional, cursos e inscricao por CPF), area administrativa
e area do aluno.

## Funcionalidades

- Publico: pagina institucional, lista de cursos, inscricao por CPF e cadastro com token.
- Admin: dashboard com indicadores, gestao de cursos, eventos e alunos.
- Admin: usuarios, configuracoes do sistema, tema, SMTP e provedores WhatsApp.
- Admin: envio de notificacoes (email/WhatsApp) com templates e preview.
- Admin: CMS institucional com sections fixas da home, ordenacao e estilos visuais.
- Admin: relatorios (cursos, eventos, matriculas, inscricoes, lista de espera, auditoria).
- Aluno: dashboard, perfil, inscricoes, historico e preferencias.
- Automacoes: fila de envios, rate limit de notificacoes e links com validade.
- Agendamentos: expiracao de matriculas, chamadas da lista de espera e lembretes.

## Acesso e perfis

- `admin`: area administrativa (`/admin`) com login em `/admin/login`.
- `aluno`: area do aluno (`/aluno`) com login em `/aluno/login`.

O controle de acesso e feito por middleware e policies.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

## Setup rapido

```bash
composer run setup
php artisan db:seed
```

## Desenvolvimento

```bash
composer run dev
```

Ou execute manualmente:

```bash
php artisan serve
php artisan queue:listen --tries=1
php artisan schedule:work
php artisan pail --timeout=0
npm run dev
```

## Operacao (producao)

- `php artisan migrate --seed`
- `php artisan queue:work`
- `php artisan schedule:run` via cron (a cada minuto)

## Usuario administrador

O usuario admin padrao e criado pelo seeder:

- Email: `admin@sindimir.local`
- Senha: `admin123`

Os valores podem ser sobrescritos via `ADMIN_NAME`, `ADMIN_EMAIL`,
`ADMIN_PASSWORD` no `.env`.

## Variaveis de ambiente principais

- APP_NAME, APP_URL, APP_ENV, APP_DEBUG, APP_LOCALE
- ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
- DASHBOARD_LIMITE_ALERTA_PERCENTUAL
- TEMA_COR_PRIMARIA, TEMA_COR_SECUNDARIA, TEMA_COR_FUNDO, TEMA_COR_TEXTO, TEMA_COR_BORDA
- SCHEDULER_LEMBRETE_DIAS_ANTES, SCHEDULER_LEMBRETE_HORARIO, SCHEDULER_LEMBRETE_EMAIL_ASSUNTO, SCHEDULER_LEMBRETE_MENSAGEM
- QUEUE_CONNECTION
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- WHATSAPP_ZAPI_ENABLED, WHATSAPP_ZAPI_BASE_URL, WHATSAPP_ZAPI_TOKEN, WHATSAPP_ZAPI_INSTANCE
- WHATSAPP_META_ENABLED, WHATSAPP_META_BASE_URL, WHATSAPP_META_TOKEN, WHATSAPP_META_PHONE_NUMBER_ID

## Banco de dados

Por padrao o projeto usa MySQL. A fila usa o driver `database` por padrao
(tabela `jobs`).

## Configuracoes em banco

Tabela `configuracoes` (via tela de configuracoes do admin):

- sistema.nome
- sistema.email_padrao
- sistema.ativo
- notificacao.email_ativo
- notificacao.whatsapp_ativo
- tema.cor_primaria
- tema.cor_secundaria
- tema.cor_fundo
- tema.cor_texto
- tema.cor_borda
- tema.cor_destaque
- whatsapp.provedor
- whatsapp.token
- whatsapp.phone_number_id
- whatsapp.webhook_url
- smtp.host
- smtp.port
- smtp.username
- smtp.password
- smtp.encryption
- smtp.from_email
- smtp.from_name
- site.meta_title
- site.meta_description
- site.footer.titulo
- site.footer.descricao
- site.footer.contato_titulo
- site.footer.contato_email
- site.footer.contato_telefone
- site.footer.endereco_titulo
- site.footer.endereco_linha1
- site.footer.endereco_linha2

Essas chaves complementam os valores de `config/app.php` e `config/mail.php`.

## Navegacao administrativa

- O item "Envio de notificacoes" abre a tela de disparo de comunicacoes.
- Auditoria nao fica mais no menu lateral; o acesso e feito pela tela de configuracoes.
- Conteudo institucional fica no menu e abre a gestao de sections da home.

## CMS institucional (home fixa)

A home institucional possui layout fixo e renderizacao por slots definidos por slug.
Esses slugs sao contrato do layout e nao devem ser alterados:

- hero
- sobre
- solucoes
- diferenciais
- contato

Tipos esperados por slot:

- hero: `hero_com_resultados`
- sobre: `cards_grid`
- solucoes: `cards_grid`
- diferenciais: `cards_grid`
- contato: `cta_card`

Regras:

- Nao criar novas sections para a home.
- Slug e tipo sao fixos para os slots acima.
- A edicao fica restrita ao conteudo interno de cada section.
- A ordenacao pode ser ajustada via admin.

## WhatsApp

Somente um provedor deve ficar ativo por vez:

- Z-API: configure `WHATSAPP_ZAPI_*` e deixe `WHATSAPP_META_ENABLED` em `false`.
- Meta Cloud API: configure `WHATSAPP_META_*` e deixe `WHATSAPP_ZAPI_ENABLED` em `false`.

Se nenhum provedor estiver ativo, o envio de WhatsApp falhara.

## Notificações para alunos

- Disparo via `POST /admin/notificacoes/disparar` (autorizações `auth` + `role:admin`). Informe `curso_id` ou `evento_curso_id` e, quando for curso único, a lista de `aluno_ids`. Para eventos, a lista pode vir vazia e o sistema usará as matrículas confirmadas.
- `NotificationService` monta assunto/mensagem com nome do aluno, nome do curso, datas do evento (quando houver), vagas disponíveis e link exclusivo.
- Os templates são configurados por tipo (select “Tipo de notificação”) e editados separadamente para Email e WhatsApp; se não houver template para um tipo, a UI mostra aviso.
- Cada disparo registra um `NotificationType` (`CURSO_DISPONIVEL`, `VAGA_ABERTA`, `LEMBRETE_CURSO`, `MATRICULA_CONFIRMADA`, `LISTA_ESPERA_CHAMADA`) em `notificacao_links` e `notificacao_logs`, mantendo a lógica atual intacta.
- Templates dinâmicos residem em `notification_templates` (notification_type + canal) e podem usar variáveis como `{{aluno_nome}}`, `{{curso_nome}}`, `{{datas}}`, `{{vagas}}`, `{{link}}`.
- Cada canal respeita um rate limit: um aluno não recebe mais de 2 notificações do mesmo tipo para o mesmo curso por dia; tentativas bloqueadas aparecem como `status = blocked` em `notificacao_logs`.
- Há um endpoint `POST /admin/notificacoes/preview` (auth + role:admin) que recebe `aluno_id`, `curso_id` e `notification_type` e retorna o assunto/corpo de email e o texto WhatsApp renderizados sem enfileirar jobs.
- Links são gerados pela tabela `notificacao_links` e valem por `NOTIFICATION_LINK_VALIDITY_MINUTES` (padrão 1440). O acesso público ocorre em `/inscricao/token/{token}` e redireciona para o formulário de cadastro.
- Jobs `SendEmailNotificationJob` e `SendWhatsAppNotificationJob` disparam as notificações com `QUEUE_CONNECTION=database` e gravam um registro em `notificacao_logs` com canal, status e eventual erro.

## Arquitetura interna

- Controllers finos, focados em orquestracao e responses.
- Services concentram regras de negocio e integracoes.
- Models representam entidades e relacionamentos.
- Policies garantem autorizacao por papel.
- Middleware aplica controle de acesso por papel.
- Observers registram auditoria automaticamente.
- Jobs enviam notificacoes por email e WhatsApp.
- Enums centralizam valores de dominio.

## Processos assincronos

- Fila baseada em banco de dados para envio de email e WhatsApp.
- Jobs: `SendEmailNotificationJob` e `SendWhatsAppNotificationJob`.

## Agendamentos

- Expirar matriculas vencidas (hora em hora).
- Chamar lista de espera por evento (hora em hora).
- Enviar lembretes de cursos (diario no horario configurado).
