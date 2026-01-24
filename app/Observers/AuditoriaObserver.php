<?php

namespace App\Observers;

use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;

class AuditoriaObserver
{
    public function __construct(private readonly AuditoriaService $auditoriaService)
    {
    }

    public function created(Model $model): void
    {
        $this->auditoriaService->registrar('criado', $model, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->auditoriaService->registrar('atualizado', $model, [
            'antes' => $model->getOriginal(),
            'depois' => $model->getChanges(),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->auditoriaService->registrar('removido', $model, $model->getAttributes());
    }
}
