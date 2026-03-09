<?php

namespace App\Services;

use App\Models\Curso;
use Illuminate\Support\Facades\DB;

class CursoService
{
    public function __construct(private readonly EventoCursoService $eventoCursoService)
    {
    }

    public function create(array $data): Curso
    {
        return DB::transaction(function () use ($data) {
            return Curso::create($data);
        });
    }

    public function update(Curso $curso, array $data): Curso
    {
        return DB::transaction(function () use ($curso, $data) {
            $curso->update($data);

            return $curso;
        });
    }

    public function delete(Curso $curso): void
    {
        DB::transaction(function () use ($curso) {
            $curso->loadMissing('eventos');

            foreach ($curso->eventos as $evento) {
                $this->eventoCursoService->delete($evento);
            }

            $curso->delete();
        });
    }
}
