<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CursosExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-cursos-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Curso',
                'Status',
                'Total de eventos',
                'Total de vagas',
                'Matrículas confirmadas',
                'Inscrições totais',
                'Lista de espera',
                'Vagas disponíveis',
                'Data de criação',
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
            $row->ativo ? 'Ativo' : 'Inativo',
            $row->eventos_total,
            $row->vagas_totais,
            $row->matriculas_confirmadas,
            $row->inscricoes_total,
            $row->lista_total,
            $row->vagas_disponiveis,
            $this->formatDate($row->created_at),
        ];
    }

    private function formatDate(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }
}
