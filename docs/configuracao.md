# Configuração

## Variáveis de ambiente principais

- ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD
- APP_NAME, APP_URL, APP_ENV, APP_DEBUG, APP_LOCALE
- SESSION_DRIVER, SESSION_DOMAIN, SESSION_LIFETIME
- DASHBOARD_LIMITE_ALERTA_PERCENTUAL
- TEMA_COR_PRIMARIA, TEMA_COR_SECUNDARIA, TEMA_COR_FUNDO, TEMA_COR_TEXTO, TEMA_COR_BORDA
- SCHEDULER_LEMBRETE_DIAS_ANTES, SCHEDULER_LEMBRETE_HORARIO, SCHEDULER_LEMBRETE_EMAIL_ASSUNTO, SCHEDULER_LEMBRETE_MENSAGEM
- QUEUE_CONNECTION
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- WHATSAPP_ZAPI_ENABLED, WHATSAPP_ZAPI_BASE_URL, WHATSAPP_ZAPI_TOKEN, WHATSAPP_ZAPI_INSTANCE
- WHATSAPP_META_ENABLED, WHATSAPP_META_BASE_URL, WHATSAPP_META_TOKEN, WHATSAPP_META_PHONE_NUMBER_ID
- NOTIFICATION_LINK_VALIDITY_MINUTES
- NOTIFICATION_MESSAGE_TEMPLATE

## Usuário administrador padrão

O seeder de admin cria o usuário com base nas chaves acima. Os valores
padrão atuais são:

- Email: `admin@sindimir.local`
- Senha: `admin123`

## Banco de dados

Por padrão o projeto usa MySQL.

## Ambiente local (evitar 419)

Para evitar erro 419 em formulários (CSRF), mantenha:

- `APP_URL` igual ao host do navegador (ex: `http://127.0.0.1:8000`)
- `SESSION_DOMAIN` vazio

## Sessão e múltiplos guards (admin/aluno)

O sistema usa guards separados (`admin` e `aluno`) para manter as áreas isoladas.
Ainda assim, o cookie/sessão do navegador é compartilhado, então **não se deve
assumir que `url.intended` pertence ao mesmo contexto** quando o usuário navega
entre `/admin` e `/aluno`.

O fluxo atual de login/2FA já é blindado para não depender de `intended` e para
redirecionar explicitamente ao dashboard correto do guard.

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

## WhatsApp

Somente um provedor deve ficar ativo por vez:

- Z-API: configure `WHATSAPP_ZAPI_*` e deixe `WHATSAPP_META_ENABLED` em `false`.
- Meta Cloud API: configure `WHATSAPP_META_*` e deixe `WHATSAPP_ZAPI_ENABLED` em `false`.

Se nenhum provedor estiver ativo, o envio de WhatsApp falhara.

## Notificações

- `NOTIFICATION_LINK_VALIDITY_MINUTES` define em minutos o tempo de vida dos links de inscrição enviados nas notificações (padrão 1440).
- Tabela `notificacao_links`: registra token exclusivo por aluno+curso (opcionalmente evento) com `expires_at`.
- Tabela `notificacao_logs`: armazena cada tentativa de envio (`canal`, `status`, `erro`, `notificacao_link_id`).
- Tabela `notification_templates`: guarda os templates ativos por `notification_type` + `canal` e define assunto/conteúdo com variáveis (`{{aluno_nome}}`, `{{curso_nome}}`, `{{datas}}`, `{{vagas}}`, `{{link}}`).