# Arquitetura

## Visão geral

Aplicação Laravel 12 voltada ao gerenciamento de cursos, inscrições e alunos.
As áreas públicas apresentam a oferta de cursos e a inscrição; as áreas logadas
se dividem em perfil administrativo (admin/usuário) e aluno.

## Camadas e responsabilidades

- Controllers finos, focados em orquestração e responses.
- Services concentram regras de negócio e integrações.
- Models representam entidades e relacionamentos.
- Policies garantem autorização por papel (admin/usuário/aluno).
- Middleware aplica controle de acesso por papel.
- Observers registram auditoria automaticamente.
- Jobs enviam notificações por email e WhatsApp.
- Enums centralizam valores de domínio.

## Módulos

- Público: página institucional, listagem de cursos e inscrição por CPF.
- Admin: gestão de cursos, eventos, alunos, auditoria e configurações.
- Aluno: área do aluno com perfil, inscrições e histórico.

## Autenticação e perfis

- Roles principais: `admin`, `usuario` e `aluno`.
- Rotas administrativas ficam sob `admin/` e exigem autenticação do **guard `admin`** e papel `admin`/`usuario` (com controle de módulos via middleware).
- Rotas da área do aluno ficam sob `aluno/` e exigem autenticação do **guard `aluno`** e papel `aluno`.

### Observacao sobre sessao e redirecionamentos

Por padrão, o Laravel pode salvar `url.intended` na **sessão** quando uma rota protegida redireciona para login. Como a sessão é compartilhada entre guards, o sistema evita depender de `intended` nos fluxos de login/2FA para impedir "login cruzado" entre admin e aluno.

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
- Logs e links de notificações persistem em `notificacao_logs` e `notificacao_links`.

## Agendamentos

- Expirar matriculas vencidas (hora em hora).
- Chamar lista de espera por evento (hora em hora).
- Enviar lembretes de cursos (diario no horario configurado).
