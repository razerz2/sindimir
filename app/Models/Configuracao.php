<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuracao extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'configuracoes';

    protected $fillable = [
        'chave',
        'valor',
        'descricao',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'array',
        ];
    }
}
