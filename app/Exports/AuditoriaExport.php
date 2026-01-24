<?php

namespace App\Exports;

use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Models\NotificationLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditoriaExport
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function download(): StreamedResponse
    {
        $filename = 'relatorio-auditoria-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fprintf($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Data/Hora da ação',
                'Usuário',
                'Email',
                'Perfil',
                'Ação',
                'Entidade',
                'ID do registro',
                'Dados antes',
                'Dados depois',
                'IP',
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
        [$before, $after] = $this->extractBeforeAfter($row->acao, $row->dados);

        return [
            $this->formatDateTime($row->data_acao),
            $row->user_nome ?? '-',
            $row->user_email ?? '-',
            $this->formatRole($row->user_role),
            $this->getActionLabel($row->acao, $row->dados),
            $this->getEntityLabel($row->entidade_type),
            $row->entidade_id ?? '-',
            $before,
            $after,
            $row->ip ?? '-',
        ];
    }

    private function getActionLabel(string $acao, ?string $dados): string
    {
        if ($acao === 'atualizado' && $this->hasStatusChange($dados)) {
            return 'Status changed';
        }

        return match ($acao) {
            'criado' => 'Created',
            'atualizado' => 'Updated',
            'removido' => 'Deleted',
            default => $acao,
        };
    }

    private function getEntityLabel(?string $entidadeType): string
    {
        return match ($entidadeType) {
            Curso::class => 'Curso',
            EventoCurso::class => 'Evento',
            Aluno::class => 'Aluno',
            ListaEspera::class => 'Inscrição',
            Matricula::class => 'Matrícula',
            NotificationLog::class => 'Notificação',
            default => 'Outro',
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractBeforeAfter(string $acao, ?string $dados): array
    {
        $decoded = $this->decodeJson($dados);

        if ($acao === 'atualizado') {
            $before = $decoded['antes'] ?? null;
            $after = $decoded['depois'] ?? null;

            return [
                $this->formatJson($before),
                $this->formatJson($after),
            ];
        }

        if ($acao === 'criado') {
            return ['-', $this->formatJson($decoded)];
        }

        if ($acao === 'removido') {
            return [$this->formatJson($decoded), '-'];
        }

        return ['-', '-'];
    }

    private function formatRole(?string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'aluno' => 'Aluno',
            default => '-',
        };
    }

    private function formatDateTime(?string $value): string
    {
        if (! $value) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y H:i');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(?string $dados): array
    {
        if (! $dados) {
            return [];
        }

        $decoded = json_decode($dados, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function formatJson(mixed $value): string
    {
        if ($value === null || $value === []) {
            return '-';
        }

        if (is_string($value)) {
            return $value;
        }

        return (string) json_encode($value, JSON_PRETTY_PRINT);
    }

    private function hasStatusChange(?string $dados): bool
    {
        return $dados ? str_contains($dados, '"status"') : false;
    }
}
