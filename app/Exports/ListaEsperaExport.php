<?php

namespace App\Exports;

use App\Enums\StatusListaEspera;
use App\Enums\StatusMatricula;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListaEsperaExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-lista-espera-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Data de entrada',
                'Aluno',
                'CPF',
                'Curso',
                'Evento',
                'Posição',
                'Status da lista de espera',
                'Data da chamada',
                'Respondeu à chamada',
                'Matrícula gerada',
                'Status da matrícula',
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
        $statusLabel = $this->getStatusListaLabel($row->status_lista, $row->matricula_id);
        $statusMatriculaLabel = $this->getStatusMatriculaLabel($row->status_matricula);

        return [
            $this->formatDateTime($row->data_entrada),
            $row->aluno_nome,
            $row->aluno_cpf,
            $row->curso_nome,
            $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio),
            $row->posicao ?? '-',
            $statusLabel,
            $this->formatDateTime($row->chamado_em),
            $row->matricula_id ? 'Sim' : 'Não',
            $row->matricula_id ? 'Sim' : 'Não',
            $statusMatriculaLabel,
        ];
    }

    private function getStatusListaLabel(string $status, ?int $matriculaId): string
    {
        if ($matriculaId) {
            return 'Convertido';
        }

        return match ($status) {
            StatusListaEspera::Chamado->value => 'Chamado',
            StatusListaEspera::Expirado->value,
            StatusListaEspera::Cancelado->value => 'Expirado',
            default => 'Aguardando',
        };
    }

    private function getStatusMatriculaLabel(?string $status): string
    {
        if (! $status) {
            return '-';
        }

        return match ($status) {
            StatusMatricula::Confirmada->value => 'Confirmada',
            StatusMatricula::Cancelada->value => 'Cancelada',
            StatusMatricula::Expirada->value => 'Vencida/Expirada',
            default => 'Pendente',
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
