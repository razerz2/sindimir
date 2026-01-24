<?php

namespace App\Exports;

use App\Enums\NotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificacoesExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-notificacoes-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Data/Hora do envio',
                'Aluno',
                'Curso',
                'Evento',
                'Tipo de notificacao',
                'Canal',
                'Status',
                'Mensagem de erro',
            ], ';');

            foreach ($this->query->cursor() as $row) {
                fputcsv($handle, $this->mapRow($row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function mapRow(object $row): array
    {
        return [
            $this->formatDateTime($row->data_envio),
            $row->aluno_nome,
            $row->curso_nome,
            $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio),
            $this->getTipoLabel($row->notification_type),
            $this->getCanalLabel($row->canal),
            $this->getStatusLabel($row->status),
            $row->erro ?: '-',
        ];
    }

    private function getTipoLabel(string $tipo): string
    {
        return match ($tipo) {
            NotificationType::CURSO_DISPONIVEL->value => 'Curso disponivel',
            NotificationType::VAGA_ABERTA->value => 'Vaga aberta',
            NotificationType::LEMBRETE_CURSO->value => 'Lembrete de curso',
            NotificationType::MATRICULA_CONFIRMADA->value => 'Matricula confirmada',
            NotificationType::LISTA_ESPERA_CHAMADA->value => 'Lista de espera chamada',
            default => $tipo,
        };
    }

    private function getCanalLabel(string $canal): string
    {
        return $canal === 'whatsapp' ? 'WhatsApp' : 'Email';
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Sucesso',
            'blocked' => 'Bloqueado',
            'failed' => 'Falha',
            default => $status,
        };
    }

    private function formatEventoLabel(?string $numeroEvento, ?string $dataInicio): string
    {
        if (! $numeroEvento) {
            return '-';
        }

        $dataLabel = $dataInicio ? CarbonImmutable::parse($dataInicio)->format('d/m/Y') : null;

        return $dataLabel
            ? "Evento {$numeroEvento} ({$dataLabel})"
            : "Evento {$numeroEvento}";
    }

    private function formatDateTime(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y H:i');
    }
}
