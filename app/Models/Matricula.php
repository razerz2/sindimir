<?php

namespace App\Models;

use App\Enums\StatusMatricula;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matricula extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'aluno_id',
        'evento_curso_id',
        'status',
        'data_confirmacao',
        'data_expiracao',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusMatricula::class,
            'data_confirmacao' => 'datetime',
            'data_expiracao' => 'datetime',
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
