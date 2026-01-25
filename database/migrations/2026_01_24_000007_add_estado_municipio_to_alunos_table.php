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
        Schema::table('alunos', function (Blueprint $table) {
            $table->foreignId('estado_residencia_id')
                ->nullable()
                ->after('bairro')
                ->constrained('estados')
                ->nullOnDelete();
            $table->foreignId('municipio_id')
                ->nullable()
                ->after('estado_residencia_id')
                ->constrained('municipios')
                ->nullOnDelete();
        });

        $ufs = DB::table('alunos')
            ->select('uf_residencia')
            ->whereNotNull('uf_residencia')
            ->distinct()
            ->pluck('uf_residencia');

        $mapEstados = [];
        foreach ($ufs as $ufRaw) {
            $uf = Str::upper(Str::squish((string) $ufRaw));
            if ($uf === '' || strlen($uf) !== 2 || isset($mapEstados[$uf])) {
                continue;
            }

            $estadoId = DB::table('estados')->insertGetId([
                'nome' => $uf,
                'uf' => $uf,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mapEstados[$uf] = $estadoId;
        }

        $alunos = DB::table('alunos')
            ->select('id', 'uf_residencia', 'municipio')
            ->whereNotNull('uf_residencia')
            ->get();

        foreach ($alunos as $aluno) {
            $uf = Str::upper(Str::squish((string) $aluno->uf_residencia));
            if ($uf === '' || strlen($uf) !== 2) {
                continue;
            }

            $estadoId = $mapEstados[$uf] ?? null;
            if (! $estadoId) {
                continue;
            }

            $municipioId = null;
            $municipioNome = Str::squish((string) ($aluno->municipio ?? ''));
            if ($municipioNome !== '') {
                $existente = DB::table('municipios')
                    ->where('estado_id', $estadoId)
                    ->where('nome', $municipioNome)
                    ->value('id');

                if (! $existente) {
                    $existente = DB::table('municipios')->insertGetId([
                        'estado_id' => $estadoId,
                        'nome' => $municipioNome,
                        'ativo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $municipioId = $existente;
            }

            DB::table('alunos')
                ->where('id', $aluno->id)
                ->update([
                    'estado_residencia_id' => $estadoId,
                    'municipio_id' => $municipioId,
                ]);
        }

        Schema::table('alunos', function (Blueprint $table) {
            $table->dropColumn(['uf_residencia', 'municipio']);
        });
    }

    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->char('uf_residencia', 2)->nullable()->after('bairro');
            $table->string('municipio')->nullable()->after('uf_residencia');
        });

        $alunos = DB::table('alunos')
            ->select('id', 'estado_residencia_id', 'municipio_id')
            ->whereNotNull('estado_residencia_id')
            ->get();

        foreach ($alunos as $aluno) {
            $uf = DB::table('estados')
                ->where('id', $aluno->estado_residencia_id)
                ->value('uf');
            $municipio = $aluno->municipio_id
                ? DB::table('municipios')->where('id', $aluno->municipio_id)->value('nome')
                : null;

            DB::table('alunos')
                ->where('id', $aluno->id)
                ->update([
                    'uf_residencia' => $uf,
                    'municipio' => $municipio,
                ]);
        }

        Schema::table('alunos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estado_residencia_id');
            $table->dropConstrainedForeignId('municipio_id');
        });
    }
};
