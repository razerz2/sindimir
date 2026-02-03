# Documentação Sindimir

Esta pasta centraliza a documentação técnica do sistema de cursos Sindimir.

## Índice

- `documentacao.md` - Documento único com visão geral, configuração e operação.
- `configuracao.md` - Variáveis de ambiente e ajustes básicos do sistema.
- `operacao.md` - Comandos e rotinas de operação.
- `arquitetura.md` - Visão geral, camadas e componentes principais.

## Nota sobre autenticação

As áreas **admin** e **aluno** usam guards separados e rotas isoladas. O fluxo
de login/2FA e redirecionamento não depende de `url.intended` para evitar login
cruzado entre contextos.
