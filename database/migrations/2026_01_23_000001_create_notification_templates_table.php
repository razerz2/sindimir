<?php

use App\Enums\NotificationType;
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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type')->default(NotificationType::CURSO_DISPONIVEL->value);
            $table->enum('canal', ['email', 'whatsapp']);
            $table->string('assunto')->nullable();
            $table->text('conteudo');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index(['notification_type', 'canal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
