<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLink extends Model
{
    use HasFactory;

    protected $table = 'notificacao_links';

    protected $fillable = [
        'aluno_id',
        'curso_id',
        'evento_curso_id',
        'token',
        'expires_at',
        'notification_type',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
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

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
