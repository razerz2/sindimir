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
            $table->time('horario_inicio')->nullable()->after('data_fim');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento_cursos', function (Blueprint $table) {
            $table->dropColumn('horario_inicio');
        });
    }
};
