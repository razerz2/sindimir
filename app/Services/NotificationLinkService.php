<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\NotificationLink;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class NotificationLinkService
{
    public function resolve(
        Aluno $aluno,
        Curso $curso,
        ?EventoCurso $evento = null,
        NotificationType $type = NotificationType::CURSO_DISPONIVEL,
        ?int $validadeMinutos = null
    ): NotificationLink
    {
        $query = NotificationLink::query()
            ->where('aluno_id', $aluno->id)
            ->where('curso_id', $curso->id);

        if ($evento) {
            $query->where('evento_curso_id', $evento->id);
        } else {
            $query->whereNull('evento_curso_id');
        }

        $link = $query->first();

        if ($link && $link->isValid() && $link->notification_type === $type->value) {
            return $link;
        }

        $attributes = [
            'aluno_id' => $aluno->id,
            'curso_id' => $curso->id,
            'evento_curso_id' => $evento?->id,
        ];

        return NotificationLink::updateOrCreate(
            $attributes,
            [
                'token' => (string) Str::uuid(),
                'expires_at' => CarbonImmutable::now()->addMinutes(
                    $validadeMinutos ?? (int) config('app.notification.link_validade_minutos', 1440)
                ),
                'notification_type' => $type->value,
            ]
        );
    }
}
