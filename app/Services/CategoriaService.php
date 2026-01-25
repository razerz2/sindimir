<?php

namespace App\Services;

use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

class CategoriaService
{
    public function create(array $data): Categoria
    {
        return DB::transaction(function () use ($data) {
            return Categoria::create($data);
        });
    }

    public function update(Categoria $categoria, array $data): Categoria
    {
        return DB::transaction(function () use ($categoria, $data) {
            $categoria->update($data);

            return $categoria;
        });
    }

    public function toggle(Categoria $categoria): Categoria
    {
        return DB::transaction(function () use ($categoria) {
            $categoria->update(['ativo' => ! $categoria->ativo]);

            return $categoria;
        });
    }

    public function delete(Categoria $categoria): bool
    {
        if ($categoria->cursos()->exists()) {
            return false;
        }

        DB::transaction(function () use ($categoria) {
            $categoria->delete();
        });

        return true;
    }
}
