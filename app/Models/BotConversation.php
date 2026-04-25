<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class BotConversation extends Model
{
    use HasFactory;

    private bool $allowContextClearForNextUpdate = false;

    private ?string $contextWriteSourceForNextUpdate = null;

    protected $table = 'bot_conversations';

    protected $fillable = [
        'channel',
        'from',
        'state',
        'context',
        'last_activity_at',
        'closed_at',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(BotMessageLog::class, 'conversation_id');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function updateWithContextPolicy(
        array $attributes,
        bool $clearContext = false,
        ?string $source = null
    ): bool {
        $this->allowContextClearForNextUpdate = $clearContext;
        $this->contextWriteSourceForNextUpdate = $source;

        try {
            return $this->update($attributes);
        } finally {
            $this->allowContextClearForNextUpdate = false;
            $this->contextWriteSourceForNextUpdate = null;
        }
    }

    protected static function booted(): void
    {
        static::updating(static function (self $conversation): void {
            $oldContext = self::normalizeContext($conversation->getOriginal('context'));
            $newContext = $conversation->isDirty('context')
                ? self::normalizeContext($conversation->getAttribute('context'))
                : $oldContext;

            if ($conversation->isDirty('context') && ! $conversation->allowContextClearForNextUpdate) {
                $newContext = self::mergeContext($oldContext, $newContext);
                $conversation->setAttribute('context', $newContext);
            }

            Log::info('BOT GLOBAL CONTEXT WRITE', [
                'source' => $conversation->contextWriteSourceForNextUpdate ?? self::resolveContextWriteSource(),
                'conversation_id' => (int) $conversation->id,
                'context_before' => $oldContext,
                'context_after' => $newContext,
                'trace' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 0, 5),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeContext(mixed $context): array
    {
        if (is_array($context)) {
            return $context;
        }

        if ($context === null) {
            return [];
        }

        if (! is_string($context)) {
            return [];
        }

        $value = trim($context);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $oldContext
     * @param array<string, mixed> $newContext
     * @return array<string, mixed>
     */
    private static function mergeContext(array $oldContext, array $newContext): array
    {
        if ($newContext === []) {
            return $oldContext;
        }

        return array_replace($oldContext, $newContext);
    }

    private static function resolveContextWriteSource(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 24);

        foreach ($trace as $frame) {
            $class = (string) ($frame['class'] ?? '');
            $type = (string) ($frame['type'] ?? '');
            $function = (string) ($frame['function'] ?? '');
            $file = (string) ($frame['file'] ?? '');

            if ($class === self::class) {
                continue;
            }

            if (str_starts_with($class, 'Illuminate\\') || str_starts_with($class, 'Laravel\\')) {
                continue;
            }

            if ($file !== '' && str_contains(str_replace('\\', '/', $file), '/vendor/')) {
                continue;
            }

            if ($class !== '' || $function !== '') {
                return trim($class . $type . $function, ':');
            }
        }

        return __METHOD__;
    }
}
