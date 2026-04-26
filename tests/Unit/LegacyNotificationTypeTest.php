<?php

namespace Tests\Unit;

use App\Enums\LegacyNotificationType;
use Tests\TestCase;

class LegacyNotificationTypeTest extends TestCase
{
    public function test_values_include_internal_admin_events(): void
    {
        $values = LegacyNotificationType::values();

        $this->assertContains(LegacyNotificationType::USUARIO_INSCRICAO_CURSO, $values);
        $this->assertContains(LegacyNotificationType::USUARIO_CANCELAMENTO_CURSO, $values);
        $this->assertContains(LegacyNotificationType::USUARIO_RESUMO_DIARIO_CURSOS, $values);
        $this->assertSameSize($values, array_unique($values));
    }
}
