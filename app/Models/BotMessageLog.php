<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMessageLog extends Model
{
    use HasFactory;

    protected $table = 'bot_message_logs';

    protected $fillable = [
        'conversation_id',
        'direction',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(BotConversation::class, 'conversation_id');
    }
}

