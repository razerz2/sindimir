<?php

namespace Database\Seeders;

use App\Models\Configuracao;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        Configuracao::updateOrCreate(
            ['chave' => 'tema.cores'],
            [
                'valor' => [
                    'primary' => '#0f3d2e',
                    'background' => '#f6f7f9',
                    'card' => '#ffffff',
                    'text' => '#1f2937',
                    'border' => '#e5e7eb',
                ],
                'descricao' => 'Cores padrão do tema',
            ]
        );
    }
}
