<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Http\Requests\Admin\NotificationDispatchRequest;
use App\Http\Requests\Admin\NotificationPreviewRequest;
use App\Services\RelatorioNotificacaoService;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class NotificationTypeRulesTest extends TestCase
{
    public function test_notification_type_enum_contains_only_three_supported_values(): void
    {
        $this->assertSame([
            'CURSO_DISPONIVEL',
            'VAGA_ABERTA',
            'LISTA_ESPERA',
        ], NotificationType::values());
    }

    public function test_dispatch_request_accepts_only_supported_notification_types(): void
    {
        $request = new NotificationDispatchRequest();
        $rules = ['notification_type' => $request->rules()['notification_type']];
        $messages = $request->messages();

        foreach (NotificationType::values() as $supportedType) {
            $validator = Validator::make(['notification_type' => $supportedType], $rules, $messages);
            $this->assertFalse($validator->fails(), "Tipo {$supportedType} deveria ser aceito.");
        }

        $validator = Validator::make(['notification_type' => 'EVENTO_CRIADO'], $rules, $messages);
        $this->assertTrue($validator->fails());
        $this->assertSame('Tipo de notificação inválido.', $validator->errors()->first('notification_type'));
    }

    public function test_preview_request_accepts_only_supported_notification_types(): void
    {
        $request = new NotificationPreviewRequest();
        $rules = ['notification_type' => $request->rules()['notification_type']];
        $messages = $request->messages();

        foreach (NotificationType::values() as $supportedType) {
            $validator = Validator::make(['notification_type' => $supportedType], $rules, $messages);
            $this->assertFalse($validator->fails(), "Tipo {$supportedType} deveria ser aceito.");
        }

        $validator = Validator::make(['notification_type' => 'MATRICULA_CONFIRMADA'], $rules, $messages);
        $this->assertTrue($validator->fails());
        $this->assertSame('Tipo de notificação inválido.', $validator->errors()->first('notification_type'));
    }

    public function test_report_label_uses_fallback_for_legacy_types(): void
    {
        $service = new RelatorioNotificacaoService();
        $method = new ReflectionMethod($service, 'getTipoLabel');
        $method->setAccessible(true);

        $this->assertSame('Lista de espera', $method->invoke($service, NotificationType::LISTA_ESPERA->value));
        $this->assertSame('Tipo removido', $method->invoke($service, 'INSCRICAO_CONFIRMAR'));
    }
}
