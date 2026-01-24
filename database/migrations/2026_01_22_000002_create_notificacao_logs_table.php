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
        Schema::create('notificacao_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->cascadeOnDelete();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->foreignId('evento_curso_id')->nullable()->constrained('evento_cursos')->nullOnDelete();
            $table->foreignId('notificacao_link_id')->nullable()->constrained('notificacao_links')->nullOnDelete();
            $table->enum('canal', ['email', 'whatsapp']);
            $table->enum('status', ['success', 'failed']);
            $table->text('erro')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacao_logs');
    }
};
