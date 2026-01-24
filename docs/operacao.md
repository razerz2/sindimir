# Operacao

## Comandos basicos

- `composer run setup` (primeiro setup com dependencias e build)
- `php artisan migrate --seed` (migracoes e seeders)
- `php artisan serve` (servidor local)
- `php artisan queue:listen --tries=1` (fila em desenvolvimento)
- `php artisan schedule:work` (agendador)
- `php artisan pail --timeout=0` (logs em tempo real)

## Comando de desenvolvimento completo

O script abaixo sobe servidor, fila, logs e Vite em paralelo:

- `composer run dev`

## Observacoes

- Ajuste as variaveis de ambiente antes de semear o admin padrao.
- Para notificacoes WhatsApp, ative apenas um provedor por vez.
- Em producao, use `php artisan queue:work` e agende `php artisan schedule:run`
  via cron (a cada minuto).