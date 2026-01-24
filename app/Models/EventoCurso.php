<?php

namespace App\Models;

use App\Enums\TurnoEvento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventoCurso extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'evento_cursos';

    protected $fillable = [
        'curso_id',
        'numero_evento',
        'data_inicio',
        'data_fim',
        'carga_horaria',
        'municipio',
        'local_realizacao',
        'turno',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'turno' => TurnoEvento::class,
            'ativo' => 'boolean',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function listaEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class);
    }
}
