<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notificacao_logs';

    protected $fillable = [
        'aluno_id',
        'curso_id',
        'evento_curso_id',
        'notificacao_link_id',
        'notification_type',
        'canal',
        'status',
        'erro',
        'mensagem',
    ];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function eventoCurso(): BelongsTo
    {
        return $this->belongsTo(EventoCurso::class);
    }

    public function notificacaoLink(): BelongsTo
    {
        return $this->belongsTo(NotificationLink::class);
    }
}
