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
- `lista-espera:chamar` (hourly)
- `vagas-disponiveis:enviar` (hourly)

## Comando de encerramento de eventos expirados

Comando manual:

- `php artisan eventos:encerrar-expirados`

Regra aplicada:

- encerra eventos com `ativo=true` e `data_fim < hoje` (timezone da aplicação);
- `data_fim = hoje` permanece ativo.
