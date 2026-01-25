<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteSection extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const HOME_SLOTS = [
        'hero',
        'sobre',
        'solucoes',
        'diferenciais',
        'contato',
    ];

    public const HOME_TYPES = [
        'hero' => 'hero_com_resultados',
        'sobre' => 'cards_grid',
        'solucoes' => 'cards_grid',
        'diferenciais' => 'cards_grid',
        'contato' => 'cta_card',
    ];

    protected $fillable = [
        'slug',
        'titulo',
        'subtitulo',
        'tipo',
        'conteudo',
        'estilo',
        'ativo',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'conteudo' => 'array',
            'estilo' => 'array',
            'ativo' => 'boolean',
            'ordem' => 'integer',
        ];
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('ordem');
    }
}
