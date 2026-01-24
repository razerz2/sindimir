<?php

namespace App\Services;

use App\Models\Aluno;
use Illuminate\Support\Facades\DB;

class AlunoService
{
    public function create(array $data, array $deficiencias = [], ?string $descricaoDeficiencia = null): Aluno
    {
        return DB::transaction(function () use ($data, $deficiencias, $descricaoDeficiencia) {
            $aluno = Aluno::create($data);
            $this->syncDeficiencias($aluno, $deficiencias, $descricaoDeficiencia);

            return $aluno;
        });
    }

    public function update(Aluno $aluno, array $data, array $deficiencias = [], ?string $descricaoDeficiencia = null): Aluno
    {
        return DB::transaction(function () use ($aluno, $data, $deficiencias, $descricaoDeficiencia) {
            $aluno->update($data);
            $this->syncDeficiencias($aluno, $deficiencias, $descricaoDeficiencia);

            return $aluno;
        });
    }

    private function syncDeficiencias(Aluno $aluno, array $deficiencias, ?string $descricaoDeficiencia): void
    {
        if (empty($deficiencias)) {
            $aluno->deficiencias()->detach();

            return;
        }

        $pivotData = [];

        foreach ($deficiencias as $deficienciaId) {
            $pivotData[$deficienciaId] = [
                'descricao' => $descricaoDeficiencia,
            ];
        }

        $aluno->deficiencias()->sync($pivotData);
    }
}
