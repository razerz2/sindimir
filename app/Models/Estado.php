<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Estado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'uf',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Estado $estado) {
            if ($estado->uf) {
                $estado->uf = Str::upper($estado->uf);
            }
        });
    }
}
