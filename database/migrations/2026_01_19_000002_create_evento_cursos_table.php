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
        Schema::create('evento_cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos');
            $table->string('numero_evento');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->unsignedInteger('carga_horaria')->default(0);
            $table->string('municipio');
            $table->string('local_realizacao');
            $table->string('turno')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_cursos');
    }
};
