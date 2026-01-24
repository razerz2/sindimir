<?php

namespace App\Services;

use App\Models\EventoCurso;
use Illuminate\Support\Facades\DB;

class EventoCursoService
{
    public function create(array $data): EventoCurso
    {
        return DB::transaction(function () use ($data) {
            return EventoCurso::create($data);
        });
    }

    public function update(EventoCurso $eventoCurso, array $data): EventoCurso
    {
        return DB::transaction(function () use ($eventoCurso, $data) {
            $eventoCurso->update($data);

            return $eventoCurso;
        });
    }

    public function delete(EventoCurso $eventoCurso): void
    {
        DB::transaction(function () use ($eventoCurso) {
            $eventoCurso->delete();
        });
    }
}
