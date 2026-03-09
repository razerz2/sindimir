# Operação

## Comandos básicos

- `composer run setup` (primeiro setup com dependencias e build)
- `php artisan migrate --seed` (migracoes e seeders)
- `php artisan serve` (servidor local)
- `php artisan queue:listen --tries=1` (fila em desenvolvimento)
- `php artisan schedule:work` (agendador)
- `php artisan pail --timeout=0` (logs em tempo real)

## Comando de desenvolvimento completo

O script abaixo sobe servidor, fila, logs e Vite em paralelo:

- `composer run dev`

## Observações

- Ajuste as variáveis de ambiente antes de semear o admin padrão.
- Para notificações WhatsApp, ative apenas um provedor por vez.
- Para contatos externos, configure credenciais Google e conecte a conta em Admin > Configurações > Google Contatos.
- Para manutenção/criação de telas no admin, seguir `docs/padrao-ui-admin.md` (breadcrumb, header, ações, ícones e texto pt-BR).
- Em produção, use `php artisan queue:work` e agende `php artisan schedule:run`
  via cron (a cada minuto).
- Para validar o isolamento de autenticação, teste logins em `/admin/login` e
  `/aluno/login` em sequência e confirme que cada um termina no seu dashboard.
## Scheduler e cron (produção)

O servidor deve executar:

- `php artisan schedule:run` a cada minuto (cron).

Exemplo de cron:

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Agendamentos relevantes:

- `bot:close-inactive` (minutely)
- `eventos:encerrar-expirados` (daily 00:05)
- `matriculas:expirar` (hourly)
- `confirmacoes:enviar` (diário, horário configurável)
- `lista-espera:chamar` (hourly)
- `vagas-disponiveis:enviar` (hourly)

Nota:

- Foi aplicada correção no `MatriculaService` para evitar `Undefined variable $diasAntes` na rotina `vagas-disponiveis:enviar`.

## Comando de encerramento de eventos expirados

Comando manual:

- `php artisan eventos:encerrar-expirados`

Regra aplicada:

- encerra eventos com `ativo=true` e `data_fim < hoje` (timezone da aplicação);
- `data_fim = hoje` permanece ativo.

## Integridade curso x evento

Diagnóstico aplicado em `2026-03-08`:

- `evento_cursos.curso_id` não aceita `NULL` e possui FK válida para `cursos.id`.
- O caso de `CURSO = "-"` na listagem de eventos ocorreu por `soft delete` de cursos já vinculados a eventos.

Consulta útil para auditoria:

```sql
SELECT e.id, e.numero_evento, e.curso_id, c.nome, c.deleted_at
FROM evento_cursos e
LEFT JOIN cursos c ON c.id = e.curso_id
WHERE c.deleted_at IS NOT NULL;
```

Tratamento recomendado para registros já existentes:

- Opção 1: restaurar o curso removido logicamente (`deleted_at = NULL`) quando ele ainda deve existir.
- Opção 2: reatribuir o evento para um curso válido.
- Opção 3: cancelar/remover o evento, se não fizer mais parte da operação.

Prevenções implementadas no código:

- criação/edição de evento rejeita `curso_id` de curso soft-deletado;
- exclusão de curso aplica soft delete em cascata nos eventos vinculados;
- migração `2026_03_09_000001_soft_delete_eventos_de_cursos_removidos` saneia eventos ativos de cursos já soft-deletados.
