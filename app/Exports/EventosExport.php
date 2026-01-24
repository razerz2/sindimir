<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventosExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-eventos-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Curso',
                'Evento',
                'Data inicio',
                'Data fim',
                'Horario',
                'Capacidade total',
                'Inscricoes',
                'Matriculas confirmadas',
                'Lista de espera',
                'Vagas disponiveis',
                'Status do evento',
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
            $row->curso_nome,
            $this->formatEventoLabel($row->numero_evento, $row->data_inicio),
            $this->formatDate($row->data_inicio),
            $this->formatDate($row->data_fim),
            $this->formatTurno($row->turno),
            $row->limite_vagas ?? 0,
            $row->inscricoes_total,
            $row->matriculas_confirmadas,
            $row->lista_aguardando,
            $row->vagas_disponiveis,
            $this->getStatusEventoLabel($row->data_inicio, $row->data_fim),
        ];
    }

    private function getStatusEventoLabel(?string $dataInicio, ?string $dataFim): string
    {
        if (! $dataInicio || ! $dataFim) {
            return 'Indefinido';
        }

        $inicio = CarbonImmutable::parse($dataInicio)->startOfDay();
        $fim = CarbonImmutable::parse($dataFim)->endOfDay();
        $hoje = CarbonImmutable::now();

        if ($hoje->lt($inicio)) {
            return 'Futuro';
        }

        if ($hoje->gt($fim)) {
            return 'Encerrado';
        }

        return 'Em andamento';
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

    private function formatDate(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }

    private function formatTurno(?string $turno): string
    {
        if (! $turno) {
            return '-';
        }

        return ucfirst(str_replace('_', ' ', $turno));
    }
}
