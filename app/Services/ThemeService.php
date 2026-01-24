<?php

namespace App\Services;

use App\Models\Configuracao;
use Illuminate\Support\Facades\Schema;

class ThemeService
{
    /**
     * @return array<string, string>
     */
    public function getThemeColors(): array
    {
        $defaults = config('app.tema', []);

        if (!Schema::hasTable('configuracoes')) {
            return [
                'cor_primaria' => $defaults['cor_primaria'] ?? '#0f3d2e',
                'cor_secundaria' => $defaults['cor_secundaria'] ?? '#ffffff',
                'cor_fundo' => $defaults['cor_fundo'] ?? '#f6f7f9',
                'cor_texto' => $defaults['cor_texto'] ?? '#1f2937',
                'cor_borda' => $defaults['cor_borda'] ?? '#e5e7eb',
            ];
        }

        $values = Configuracao::query()
            ->whereIn('chave', [
                'tema.cor_primaria',
                'tema.cor_secundaria',
                'tema.cor_fundo',
                'tema.cor_texto',
                'tema.cor_borda',
            ])
            ->pluck('valor', 'chave');

        return [
            'cor_primaria' => $this->normalizeColor($values->get('tema.cor_primaria'), $defaults['cor_primaria'] ?? '#0f3d2e'),
            'cor_secundaria' => $this->normalizeColor($values->get('tema.cor_secundaria'), $defaults['cor_secundaria'] ?? '#ffffff'),
            'cor_fundo' => $this->normalizeColor($values->get('tema.cor_fundo'), $defaults['cor_fundo'] ?? '#f6f7f9'),
            'cor_texto' => $this->normalizeColor($values->get('tema.cor_texto'), $defaults['cor_texto'] ?? '#1f2937'),
            'cor_borda' => $this->normalizeColor($values->get('tema.cor_borda'), $defaults['cor_borda'] ?? '#e5e7eb'),
        ];
    }

    public function getCssVariables(): string
    {
        $colors = $this->getThemeColors();

        return implode(' ', [
            "--color-primary: {$colors['cor_primaria']};",
            "--color-card: {$colors['cor_secundaria']};",
            "--color-background: {$colors['cor_fundo']};",
            "--color-text: {$colors['cor_texto']};",
            "--color-border: {$colors['cor_borda']};",
        ]);
    }

    private function normalizeColor(mixed $value, string $default): string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return $default;
    }
}
