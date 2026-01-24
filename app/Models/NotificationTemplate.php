<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type',
        'canal',
        'assunto',
        'conteudo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
