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
        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->text('mensagem')->nullable()->after('erro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->dropColumn('mensagem');
        });
    }
};
