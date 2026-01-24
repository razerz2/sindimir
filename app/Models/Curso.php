<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curso extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'descricao',
        'categoria',
        'validade',
        'limite_vagas',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'validade' => 'date',
            'ativo' => 'boolean',
        ];
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoCurso::class);
    }
}
