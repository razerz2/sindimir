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
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->cascadeOnDelete();
            $table->foreignId('evento_curso_id')->constrained('evento_cursos')->cascadeOnDelete();
            $table->string('status')->default('pendente');
            $table->timestamp('data_confirmacao')->nullable();
            $table->timestamp('data_expiracao')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['aluno_id', 'evento_curso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
