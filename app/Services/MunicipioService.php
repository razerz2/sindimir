<?php

namespace App\Services;

use App\Models\Municipio;
use Illuminate\Support\Facades\DB;

class MunicipioService
{
    public function create(array $data): Municipio
    {
        return DB::transaction(function () use ($data) {
            return Municipio::create($data);
        });
    }

    public function update(Municipio $municipio, array $data): Municipio
    {
        return DB::transaction(function () use ($municipio, $data) {
            $municipio->update($data);

            return $municipio;
        });
    }

    public function toggle(Municipio $municipio): Municipio
    {
        return DB::transaction(function () use ($municipio) {
            $municipio->update(['ativo' => ! $municipio->ativo]);

            return $municipio;
        });
    }

    public function delete(Municipio $municipio): bool
    {
        if ($municipio->alunos()->exists()) {
            return false;
        }

        DB::transaction(function () use ($municipio) {
            $municipio->delete();
        });

        return true;
    }
}
