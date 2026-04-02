<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_conversations MODIFY channel ENUM('meta','zapi','waha','evolution') NOT NULL"
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
DO $$
DECLARE constraint_name text;
BEGIN
    FOR constraint_name IN
        SELECT c.conname
        FROM pg_constraint c
        INNER JOIN pg_class t ON t.oid = c.conrelid
        WHERE t.relname = 'bot_conversations'
          AND c.contype = 'c'
          AND pg_get_constraintdef(c.oid) ILIKE '%channel%'
    LOOP
        EXECUTE format('ALTER TABLE bot_conversations DROP CONSTRAINT %I', constraint_name);
    END LOOP;

    ALTER TABLE bot_conversations ALTER COLUMN channel TYPE VARCHAR(30);
    ALTER TABLE bot_conversations
        ADD CONSTRAINT bot_conversations_channel_check
        CHECK (channel IN ('meta', 'zapi', 'waha', 'evolution'));
END
$$;
SQL
            );
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

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE bot_conversations MODIFY channel ENUM('meta','zapi','waha') NOT NULL"
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
DO $$
DECLARE constraint_name text;
BEGIN
    FOR constraint_name IN
        SELECT c.conname
        FROM pg_constraint c
        INNER JOIN pg_class t ON t.oid = c.conrelid
        WHERE t.relname = 'bot_conversations'
          AND c.contype = 'c'
          AND pg_get_constraintdef(c.oid) ILIKE '%channel%'
    LOOP
        EXECUTE format('ALTER TABLE bot_conversations DROP CONSTRAINT %I', constraint_name);
    END LOOP;

    ALTER TABLE bot_conversations ALTER COLUMN channel TYPE VARCHAR(30);
    ALTER TABLE bot_conversations
        ADD CONSTRAINT bot_conversations_channel_check
        CHECK (channel IN ('meta', 'zapi', 'waha'));
END
$$;
SQL
            );
        }
    }
};
