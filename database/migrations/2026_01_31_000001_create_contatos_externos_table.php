<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos_externos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('telefone', 20)->unique();
            $table->string('origem', 20)->default('google');
            $table->string('google_contact_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos_externos');
    }
};
