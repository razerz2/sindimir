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
        Schema::table('notificacao_links', function (Blueprint $table) {
            $table->string('notification_type')->default(NotificationType::CURSO_DISPONIVEL->value)->after('token');
        });

        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->string('notification_type')->default(NotificationType::CURSO_DISPONIVEL->value)->after('notificacao_link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notificacao_links', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });

        Schema::table('notificacao_logs', function (Blueprint $table) {
            $table->dropColumn('notification_type');
        });
    }
};
