<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bot_conversations')) {
            return;
        }

        $hasClosedAt = Schema::hasColumn('bot_conversations', 'closed_at');
        $hasClosedReason = Schema::hasColumn('bot_conversations', 'closed_reason');

        if (! $hasClosedAt || ! $hasClosedReason) {
            Schema::table('bot_conversations', function (Blueprint $table) use ($hasClosedAt, $hasClosedReason) {
                if (! $hasClosedAt) {
                    $table->timestamp('closed_at')->nullable()->after('last_activity_at')->index();
                }

                if (! $hasClosedReason) {
                    $table->string('closed_reason', 50)->nullable()->after('closed_at')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bot_conversations')) {
            return;
        }

        $hasClosedAt = Schema::hasColumn('bot_conversations', 'closed_at');
        $hasClosedReason = Schema::hasColumn('bot_conversations', 'closed_reason');

        if ($hasClosedAt || $hasClosedReason) {
            Schema::table('bot_conversations', function (Blueprint $table) use ($hasClosedAt, $hasClosedReason) {
                if ($hasClosedAt) {
                    $table->dropColumn('closed_at');
                }

                if ($hasClosedReason) {
                    $table->dropColumn('closed_reason');
                }
            });
        }
    }
};

