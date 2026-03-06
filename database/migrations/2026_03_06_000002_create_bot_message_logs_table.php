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
        Schema::create('bot_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('bot_conversations')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_message_logs');
    }
};

