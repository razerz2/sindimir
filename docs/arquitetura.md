# Arquitetura

## Visao geral

Aplicacao Laravel 12 voltada ao gerenciamento de cursos, inscricoes e alunos.
As areas publicas apresentam a oferta de cursos e a inscricao; as areas logadas
se dividem em perfil de administrador e aluno.

## Camadas e responsabilidades

- Controllers finos, focados em orquestracao e responses.
- Services concentram regras de negocio e integracoes.
- Models representam entidades e relacionamentos.
- Policies garantem autorizacao por papel (admin/aluno).
- Middleware aplica controle de acesso por papel.
- Observers registram auditoria automaticamente.
- Jobs enviam notificacoes por email e WhatsApp.
- Enums centralizam valores de dominio.

## Modulos

- Publico: pagina institucional, listagem de cursos e inscricao por CPF.
- Admin: gestao de cursos, eventos, alunos, auditoria e configuracoes.
- Aluno: area do aluno com perfil, inscricoes e historico.

## Autenticacao e perfis

- Roles principais: `admin` e `aluno`.
- Rotas administrativas ficam sob `admin/` e exigem autenticacao e papel `admin`.
- Rotas da area do aluno ficam sob `aluno/` e exigem autenticacao e papel `aluno`.

## Principais services

- `AlunoService`
- `AuditoriaService`
- `ConfiguracaoService`
- `CursoService`
- `DashboardService`
- `EventoCursoService`
- `MatriculaService`
- `NotificationService`
- `PublicInscricaoService`
- `ReminderService`
- `SiteSectionService`
- `ThemeService`
- `WhatsAppService`

## Integracoes e suporte

- Notificacoes: email e WhatsApp via `NotificationService` e `WhatsAppService`.
- Tipos de notificação (`NotificationTypeEnum`) enriquecem `notificacao_links` e `notificacao_logs` sem alterar o fluxo de envio.
- Templates são persistidos em `notification_templates`, permitindo conteúdo e assunto personalizados por canal.
- Tema: cores default definidas em `config/app.php` e override via tabela `configuracoes`.
- Auditoria: eventos de criacao/alteracao registrados via `AuditoriaObserver`.
- Home institucional: layout fixo com slots definidos por slug na tabela `site_sections`.

## Processos assincronos

- Fila baseada em banco de dados para envio de email e WhatsApp.
- Jobs: `SendEmailNotificationJob` e `SendWhatsAppNotificationJob`.
- Logs e links de notificacoes persistem em `notificacao_logs` e `notificacao_links`.

## Agendamentos

- Expirar matriculas vencidas (hora em hora).
- Chamar lista de espera por evento (hora em hora).
- Enviar lembretes de cursos (diario no horario configurado).
