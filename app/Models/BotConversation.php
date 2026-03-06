<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotConversation extends Model
{
    use HasFactory;

    protected $table = 'bot_conversations';

    protected $fillable = [
        'channel',
        'from',
        'state',
        'context',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(BotMessageLog::class, 'conversation_id');
    }
}

