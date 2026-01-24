<?php

namespace App\Models;

use App\Enums\StatusListaEspera;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListaEspera extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'lista_espera';

    protected $fillable = [
        'aluno_id',
        'evento_curso_id',
        'status',
        'posicao',
        'chamado_em',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusListaEspera::class,
            'posicao' => 'integer',
            'chamado_em' => 'datetime',
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function eventoCurso(): BelongsTo
    {
        return $this->belongsTo(EventoCurso::class);
    }
}
