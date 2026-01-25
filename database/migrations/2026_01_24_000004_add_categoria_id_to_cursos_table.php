<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->foreignId('categoria_id')
                ->nullable()
                ->after('descricao')
                ->constrained('categorias');
        });

        $nomes = DB::table('cursos')
            ->select('categoria')
            ->whereNotNull('categoria')
            ->where('categoria', '!=', '')
            ->distinct()
            ->pluck('categoria');

        $map = [];
        foreach ($nomes as $nomeOriginal) {
            $nome = Str::squish((string) $nomeOriginal);
            if ($nome === '' || isset($map[$nome])) {
                continue;
            }

            $slug = $this->uniqueSlug($nome);
            $id = DB::table('categorias')->insertGetId([
                'nome' => $nome,
                'slug' => $slug,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $map[$nome] = $id;
        }

        $cursos = DB::table('cursos')
            ->select('id', 'categoria')
            ->whereNotNull('categoria')
            ->get();

        foreach ($cursos as $curso) {
            $nome = Str::squish((string) ($curso->categoria ?? ''));
            if ($nome === '' || ! isset($map[$nome])) {
                continue;
            }

            DB::table('cursos')
                ->where('id', $curso->id)
                ->update(['categoria_id' => $map[$nome]]);
        }

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('cursos', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('descricao');
        });

        $categorias = DB::table('categorias')->pluck('nome', 'id');
        $cursos = DB::table('cursos')
            ->select('id', 'categoria_id')
            ->whereNotNull('categoria_id')
            ->get();

        foreach ($cursos as $curso) {
            $nome = $categorias[$curso->categoria_id] ?? null;
            if (! $nome) {
                continue;
            }

            DB::table('cursos')
                ->where('id', $curso->id)
                ->update(['categoria' => $nome]);
        }

        Schema::table('cursos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_id');
        });
    }

    private function uniqueSlug(string $nome): string
    {
        $base = Str::slug($nome);
        $slug = $base;
        $suffix = 2;

        while (DB::table('categorias')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
};
