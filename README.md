# Sindimir - Sistema de Cursos

Aplicacao Laravel para gestao de cursos, inscricoes e alunos do Sindimir.
Inclui area publica com cursos e inscricao por CPF, area administrativa e
area do aluno.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (arquivo em `database/database.sqlite`)

## Setup rapido

```bash
composer run setup
```

Para dados iniciais (admin e configuracoes padrão), execute:

```bash
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

## Usuario administrador

O usuario admin padrao e criado pelo seeder:

- Email: `admin@sindimir.local`
- Senha: `admin123`

Os valores podem ser sobrescritos via `ADMIN_NAME`, `ADMIN_EMAIL`,
`ADMIN_PASSWORD` no `.env`.

## Documentacao

Consulte a pasta `docs/` para detalhes de configuracao, operacao e arquitetura.
# sindimir
