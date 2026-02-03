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
- Em produção, use `php artisan queue:work` e agende `php artisan schedule:run`
  via cron (a cada minuto).
- Para validar o isolamento de autenticação, teste logins em `/admin/login` e
  `/aluno/login` em sequência e confirme que cada um termina no seu dashboard.