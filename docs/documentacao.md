# Documentação Única Sindimir

Documento consolidado com visão geral, configuração e operação do sistema.

## Visão geral

Aplicação Laravel 12 para gerenciamento de cursos, inscrições e alunos. Há
área pública (institucional, cursos e inscrição por CPF), área administrativa
e área do aluno.

## Funcionalidades

- Público: página institucional, lista de cursos, inscrição por CPF e cadastro com token.
- Admin: dashboard com indicadores, gestão de cursos, eventos e alunos.
- Admin: usuários, configurações do sistema, tema, SMTP e provedores WhatsApp.
- Admin: gestão de usuários com permissões por módulo e nome de exibição.
- Admin: envio de notificações (email/WhatsApp) com templates e preview.
- Admin: CMS institucional com sections fixas da home, ordenação e estilos visuais.
- Admin: relatórios (cursos, eventos, matrículas, inscrições, lista de espera, auditoria).
- Aluno: dashboard, perfil, inscrições, histórico e preferências.
- Automacoes: fila de envios, rate limit de notificacoes e links com validade.
- Agendamentos: expiração de matrículas, chamadas da lista de espera e lembretes.
- Segurança: autenticação em dois fatores (2FA) por email ou WhatsApp.

## Acesso e perfis

- `admin`: área administrativa (`/admin`) com login em `/admin/login`.
- `usuario`: perfil administrativo com acesso por módulos configurados.
- `aluno`: área do aluno (`/aluno`) com login em `/aluno/login`.

O controle de acesso é feito por middleware e policies.
O middleware `module-access` limita o acesso do perfil `usuario` por módulos.

## Autenticação (guards) e isolamento entre áreas

O sistema utiliza **guards separados** para evitar interferência entre sessão do admin e do aluno:

- **Guard `admin`**: usado exclusivamente na área administrativa (roles `admin` e `usuario`).
- **Guard `aluno`**: usado exclusivamente na área do aluno (role `aluno`).

### Rotas de autenticação (nomes)

- **Admin**
  - Login: `GET /admin/login` (`admin.login`) e `POST /admin/login` (`admin.login.store`)
  - Logout: `POST /admin/logout` (`admin.logout`) e `GET /admin/logout` (`admin.logout.get`)
- **Aluno**
  - Login: `GET /aluno/login` (`aluno.login`) e `POST /aluno/login` (`aluno.login.store`)
  - Logout: `POST /aluno/logout` (`aluno.logout`) e `GET /aluno/logout` (`aluno.logout.get`)

### Middlewares e redirecionamentos

- Rotas admin usam `auth:admin` / `guest:admin`.
- Rotas aluno usam `auth:aluno` / `guest:aluno`.
- Ao tentar acessar uma rota protegida sem autenticação:
  - Admin redireciona para `/admin/login`
  - Aluno redireciona para `/aluno/login`

### Blindagem contra “login cruzado” (url.intended)

O Laravel armazena `url.intended` na **mesma sessão**. Como a sessão é compartilhada entre guards, isso pode causar redirecionamento incorreto ao fazer login em outra área.

Por isso, o fluxo de **login** e **2FA**:

- **não depende de `intended`** para decidir o destino;
- **limpa `url.intended`** e redireciona explicitamente para o dashboard do contexto:
  - Admin → `/admin/dashboard`
  - Aluno → `/aluno/dashboard`

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+

## Setup rápido

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

## Operação (produção)

- `php artisan migrate --seed`
- `php artisan queue:work`
- `php artisan schedule:run` via cron (a cada minuto)

## Usuário administrador

O usuário admin padrão é criado pelo seeder:

- Email: `admin@sindimir.local`
- Senha: `admin123`

Os valores podem ser sobrescritos via `ADMIN_NAME`, `ADMIN_EMAIL`,
`ADMIN_PASSWORD` no `.env`.

## Variáveis de ambiente principais

- APP_NAME, APP_URL, APP_ENV, APP_DEBUG, APP_LOCALE
- ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
- DASHBOARD_LIMITE_ALERTA_PERCENTUAL
- TEMA_COR_PRIMARIA, TEMA_COR_SECUNDARIA, TEMA_COR_FUNDO, TEMA_COR_TEXTO, TEMA_COR_BORDA
- SCHEDULER_LEMBRETE_DIAS_ANTES, SCHEDULER_LEMBRETE_HORARIO, SCHEDULER_LEMBRETE_EMAIL_ASSUNTO, SCHEDULER_LEMBRETE_MENSAGEM
- QUEUE_CONNECTION
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- WHATSAPP_ZAPI_ENABLED, WHATSAPP_ZAPI_BASE_URL, WHATSAPP_ZAPI_TOKEN, WHATSAPP_ZAPI_INSTANCE
- WHATSAPP_ZAPI_CLIENT_TOKEN
- WHATSAPP_META_ENABLED, WHATSAPP_META_BASE_URL, WHATSAPP_META_TOKEN, WHATSAPP_META_PHONE_NUMBER_ID

## Banco de dados

Por padrão o projeto usa MySQL. A fila usa o driver `database` por padrão
(tabela `jobs`).

## Configurações em banco

Tabela `configuracoes` (via tela de configurações do admin):

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
- tema.logo
- tema.favicon
- tema.background_main_imagem
- tema.background_main_overlay
- tema.background_main_posicao
- tema.background_main_tamanho
- whatsapp.provedor
- whatsapp.token
- whatsapp.client_token
- whatsapp.phone_number_id
- whatsapp.webhook_url
- smtp.host
- smtp.port
- smtp.username
- smtp.password
- smtp.encryption
- smtp.from_email
- smtp.from_name
- seguranca.2fa.ativo
- seguranca.2fa.perfil
- seguranca.2fa.canal
- seguranca.2fa.expiracao_minutos
- seguranca.2fa.max_tentativas
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

## Tema (público)

As variáveis de tema usadas na área pública são compartilhadas globalmente no
Blade via `AppServiceProvider`, evitando uso de `@php` nas views. Variáveis
disponiveis:

- `$themeFavicon`
- `$themeLogo`
- `$themeBackgroundImage`
- `$themeBackgroundOverlay`
- `$themeBackgroundPosition`
- `$themeBackgroundSize`

## Navegação administrativa

- O item "Envio de notificações" abre a tela de disparo de comunicações.
- Auditoria não fica mais no menu lateral; o acesso é feito pela tela de configurações.
- Conteúdo institucional fica no menu e abre a gestão de sections da home.

## CMS institucional (home fixa)

A home institucional possui layout fixo e renderização por slots definidos por slug.
Esses slugs são contrato do layout e não devem ser alterados:

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

- Não criar novas sections para a home.
- Slug e tipo são fixos para os slots acima.
- A edição fica restrita ao conteúdo interno de cada section.
- A ordenação pode ser ajustada via admin.

## WhatsApp

Somente um provedor deve ficar ativo por vez:

- Z-API: configure `WHATSAPP_ZAPI_*` e deixe `WHATSAPP_META_ENABLED` em `false`.
- Meta Cloud API: configure `WHATSAPP_META_*` e deixe `WHATSAPP_ZAPI_ENABLED` em `false`.

Se nenhum provedor estiver ativo, o envio de WhatsApp falhara.

## Autenticação em dois fatores (2FA)

- Ativação e parâmetros via configurações do admin (segurança).
- Perfis: `admin`, `aluno` ou `ambos`.
- Canal: email ou WhatsApp.
- Expiração e limite de tentativas configuráveis.
- Rotas: `/2fa` (tela), `POST /2fa` (validar) e `POST /2fa/reenviar` (novo código).

O código é enviado ao email do usuário ou ao WhatsApp do aluno (quando disponível).

## Notificações para alunos

- Disparo via `POST /admin/notificacoes/disparar` (autorizações `auth` + `role:admin`). Informe `curso_id` ou `evento_curso_id` e, quando for curso único, a lista de `aluno_ids`. Para eventos, a lista pode vir vazia e o sistema usará as matrículas confirmadas.
- `NotificationService` monta assunto/mensagem com nome do aluno, nome do curso, datas do evento (quando houver), vagas disponíveis e link exclusivo.
- Os templates são configurados por tipo (select “Tipo de notificação”) e editados separadamente para Email e WhatsApp; se não houver template para um tipo, a UI mostra aviso.
- Cada disparo registra um `NotificationType` (`EVENTO_CRIADO`, `EVENTO_CANCELADO`, `INSCRICAO_CONFIRMAR`, `CURSO_DISPONIVEL`, `VAGA_ABERTA`, `LEMBRETE_CURSO`, `MATRICULA_CONFIRMADA`, `LISTA_ESPERA_CHAMADA`) em `notificacao_links` e `notificacao_logs`, mantendo a lógica atual intacta.
- Templates dinâmicos residem em `notification_templates` (notification_type + canal) e podem usar variáveis como `{{aluno_nome}}`, `{{curso_nome}}`, `{{datas}}`, `{{vagas}}`, `{{link}}`.
- Cada canal respeita um rate limit: um aluno não recebe mais de 2 notificações do mesmo tipo para o mesmo curso por dia; tentativas bloqueadas aparecem como `status = blocked` em `notificacao_logs`.
- Há um endpoint `POST /admin/notificacoes/preview` (auth + role:admin) que recebe `aluno_id`, `curso_id` e `notification_type` e retorna o assunto/corpo de email e o texto WhatsApp renderizados sem enfileirar jobs.
- Links são gerados pela tabela `notificacao_links` e valem por `NOTIFICATION_LINK_VALIDITY_MINUTES` (padrão 1440). O acesso público ocorre em `/inscricao/token/{token}` (inscrição) e `/inscricao/confirmar/{token}` (confirmação).
- Jobs `SendEmailNotificationJob` e `SendWhatsAppNotificationJob` disparam as notificações com `QUEUE_CONNECTION=database` e gravam um registro em `notificacao_logs` com canal, status e eventual erro.
- O prazo de confirmação é configurado em `notificacao.auto.inscricao_confirmacao.tempo_limite_horas` (padrão 24).
- A lista de espera suporta modos `todos` e `sequencial` em `notificacao.auto.lista_espera.modo`, com intervalo de envio em `notificacao.auto.lista_espera.intervalo_minutos`.
- Curso disponível respeita `notificacao.auto.curso_disponivel.horario_envio` (padrão 08:00) e `notificacao.auto.curso_disponivel.dias_antes` (padrão 0); fora do horário apenas ignora o envio.
- Inscrições em eventos geram notificação de confirmação (`INSCRICAO_CONFIRMAR`); se não confirmadas até o prazo, a matrícula expira e a lista de espera é acionada.
- Cancelamentos de evento disparam notificações para inscritos e lista de espera (`EVENTO_CANCELADO`).

## Arquitetura interna

- Controllers finos, focados em orquestração e responses.
- Services concentram regras de negocio e integracoes.
- Models representam entidades e relacionamentos.
- Policies garantem autorização por papel.
- Middleware aplica controle de acesso por papel.
- Observers registram auditoria automaticamente.
- Jobs enviam notificacoes por email e WhatsApp.
- Enums centralizam valores de dominio.

## Processos assíncronos

- Fila baseada em banco de dados para envio de email e WhatsApp.
- Jobs: `SendEmailNotificationJob` e `SendWhatsAppNotificationJob`.

## Agendamentos

- Expirar matrículas vencidas (hora em hora).
- Chamar lista de espera por evento (hora em hora).
- Enviar notificações de curso disponível (hora em hora; envio efetivo só no horário configurado).
- Enviar lembretes de cursos (diário no horário configurado).
