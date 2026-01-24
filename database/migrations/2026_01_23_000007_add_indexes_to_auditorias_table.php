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
        Schema::table('auditorias', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('acao');
            $table->index('entidade_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['acao']);
            $table->dropIndex(['entidade_type']);
            $table->dropIndex(['created_at']);
        });
    }
};
