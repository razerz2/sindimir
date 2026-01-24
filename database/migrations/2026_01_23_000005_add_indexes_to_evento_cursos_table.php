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
        Schema::table('evento_cursos', function (Blueprint $table) {
            $table->index('curso_id');
            $table->index('data_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento_cursos', function (Blueprint $table) {
            $table->dropIndex(['curso_id']);
            $table->dropIndex(['data_inicio']);
        });
    }
};
