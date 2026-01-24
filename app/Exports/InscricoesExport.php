<?php

namespace App\Exports;

use App\Enums\StatusMatricula;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscricoesExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-inscricoes-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Data da inscrição',
                'Aluno',
                'CPF',
                'Curso',
                'Evento',
                'Status da inscrição',
                'Matrícula gerada',
                'Status da matrícula',
                'Origem da inscrição',
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
        $statusInscricaoLabel = $this->getStatusInscricaoLabel($row->status_inscricao);
        $statusMatriculaLabel = $this->getStatusMatriculaLabel($row->status_matricula);
        $origemLabel = $this->getOrigemLabel((bool) $row->tem_notificacao, (bool) $row->tem_manual);

        return [
            $this->formatDateTime($row->data_inscricao),
            $row->aluno_nome,
            $row->aluno_cpf,
            $row->curso_nome,
            $this->formatEventoLabel($row->evento_numero, $row->evento_data_inicio),
            $statusInscricaoLabel,
            $row->matricula_id ? 'Sim' : 'Não',
            $statusMatriculaLabel,
            $origemLabel,
        ];
    }

    private function getStatusInscricaoLabel(string $status): string
    {
        return match ($status) {
            'convertida' => 'Convertida',
            'cancelada' => 'Cancelada',
            default => 'Ativa',
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

    private function getOrigemLabel(bool $temNotificacao, bool $temManual): string
    {
        if ($temNotificacao) {
            return 'Notificação';
        }

        if ($temManual) {
            return 'Manual';
        }

        return 'Pública';
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
