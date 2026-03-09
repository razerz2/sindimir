<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cursos')
            ->select('id', 'deleted_at')
            ->whereNotNull('deleted_at')
            ->orderBy('id')
            ->chunkById(200, function ($cursos): void {
                foreach ($cursos as $curso) {
                    DB::table('evento_cursos')
                        ->where('curso_id', $curso->id)
                        ->whereNull('deleted_at')
                        ->update([
                            'deleted_at' => $curso->deleted_at ?? now(),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Migração de saneamento de dados sem rollback automático.
    }
};
