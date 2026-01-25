<?php

namespace App\Services;

use App\Models\Estado;
use Illuminate\Support\Facades\DB;

class EstadoService
{
    public function create(array $data): Estado
    {
        return DB::transaction(function () use ($data) {
            return Estado::create($data);
        });
    }

    public function update(Estado $estado, array $data): Estado
    {
        return DB::transaction(function () use ($estado, $data) {
            $estado->update($data);

            return $estado;
        });
    }

    public function toggle(Estado $estado): Estado
    {
        return DB::transaction(function () use ($estado) {
            $estado->update(['ativo' => ! $estado->ativo]);

            return $estado;
        });
    }

    public function delete(Estado $estado): bool
    {
        if ($estado->municipios()->exists()) {
            return false;
        }

        DB::transaction(function () use ($estado) {
            $estado->delete();
        });

        return true;
    }
}
