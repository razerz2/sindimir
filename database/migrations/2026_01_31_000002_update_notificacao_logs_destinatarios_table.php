<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->dropForeign(['aluno_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notificacao_logs MODIFY aluno_id BIGINT UNSIGNED NULL');
        }

        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->foreign('aluno_id')->references('id')->on('alunos')->nullOnDelete();
            $table->foreignId('contato_externo_id')
                ->nullable()
                ->after('aluno_id')
                ->constrained('contatos_externos')
                ->nullOnDelete();
            $table->string('tipo_destinatario', 20)
                ->default('aluno')
                ->after('contato_externo_id');
            $table->index('tipo_destinatario');
            $table->index('contato_externo_id');
        });
    }

    public function down(): void
    {
        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->dropIndex(['tipo_destinatario']);
            $table->dropIndex(['contato_externo_id']);
            $table->dropForeign(['contato_externo_id']);
            $table->dropColumn('contato_externo_id');
            $table->dropColumn('tipo_destinatario');
            $table->dropForeign(['aluno_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notificacao_logs MODIFY aluno_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->foreign('aluno_id')->references('id')->on('alunos')->cascadeOnDelete();
        });
    }
};
