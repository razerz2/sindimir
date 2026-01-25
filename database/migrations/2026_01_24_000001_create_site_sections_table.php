<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo')->nullable();
            $table->text('subtitulo')->nullable();
            $table->string('tipo');
            $table->json('conteudo');
            $table->json('estilo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sections');
    }
};
