# Documentacao Sindimir

Esta pasta centraliza a documentacao tecnica do sistema de cursos Sindimir.

## Indice

- `documentacao.md` - Documento unico com visao geral, configuracao e operacao.
- `configuracao.md` - Variaveis de ambiente e ajustes basicos do sistema.
- `operacao.md` - Comandos e rotinas de operacao.
- `arquitetura.md` - Visao geral, camadas e componentes principais.
- `bot.md` - Fluxos do BOT, webhooks, configuracoes e scheduler.
- `whatsapp.md` - Arquitetura multi-provedor de WhatsApp (meta, zapi, waha, evolution), webhooks, health e QA.
- `padrao-acoes-icone-admin.md` - Padrao de icones em acoes admin (botoes/links de acao).
- `padrao-ui-admin.md` - Padrao estrutural de interface do admin (breadcrumb, header, acoes, icones e texto).
- Integracoes: Google Contacts para contatos externos e notificacoes configuraveis.

## Nota sobre autenticacao

As areas **admin** e **aluno** usam guards separados e rotas isoladas. O fluxo
de login/2FA e redirecionamento nao depende de `url.intended` para evitar login
cruzado entre contextos.
