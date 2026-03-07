<?php

namespace App\Console\Commands;

use App\Models\EventoCurso;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CloseExpiredEventosCommand extends Command
{
    protected $signature = 'eventos:encerrar-expirados';

    protected $description = 'Encerra eventos expirados (data_fim no passado), definindo ativo=false.';

    public function handle(): int
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone)->toDateString();

        $baseQuery = EventoCurso::query()
            ->where('ativo', true)
            ->whereNotNull('data_fim')
            ->whereDate('data_fim', '<', $today);

        $found = (clone $baseQuery)->count();
        $updated = $baseQuery->update(['ativo' => false]);

        $this->info("Hoje: {$today} ({$timezone})");
        $this->info("Eventos expirados encontrados: {$found}");
        $this->info("Eventos encerrados (ativo=false): {$updated}");

        return self::SUCCESS;
    }
}

