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
        Schema::create('bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['meta', 'zapi', 'waha', 'evolution']);
            $table->string('from', 30);
            $table->string('state', 120)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->string('closed_reason', 50)->nullable()->index();
            $table->timestamps();

            $table->unique(['channel', 'from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_conversations');
    }
};
