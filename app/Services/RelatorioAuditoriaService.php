<?php

namespace App\Services;

use App\Exports\AuditoriaExport;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\EventoCurso;
use App\Models\ListaEspera;
use App\Models\Matricula;
use App\Models\NotificationLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RelatorioAuditoriaService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $perPage = $this->resolvePerPage($filtros);
        $query = $this->buildQuery($filtros);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($row) => $this->mapRow($row));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarExcel(array $filtros): Response
    {
        $query = $this->buildQuery($filtros, true);
        $export = new AuditoriaExport($query);

        return $export->download();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiltroData(): array
    {
        $usuarios = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $userOptions = $usuarios->map(fn (User $user) => [
            'value' => $user->id,
            'label' => trim("{$user->name} ({$user->email})"),
        ])->all();

        return [
            'userOptions' => $userOptions,
            'actionOptions' => $this->getActionOptions(),
            'entityOptions' => $this->getEntityOptions(),
            'perPageOptions' => $this->getPerPageOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function buildQuery(array $filtros, bool $forExport = false): Builder
    {
        $query = DB::table('auditorias')
            ->leftJoin('users', 'users.id', '=', 'auditorias.user_id')
            ->select([
                'auditorias.id',
                'auditorias.created_at as data_acao',
                'auditorias.acao',
                'auditorias.entidade_type',
                'auditorias.entidade_id',
                'auditorias.dados',
                'auditorias.ip',
                'users.name as user_nome',
                'users.email as user_email',
                'users.role as user_role',
            ]);

        $this->applyFilters($query, $filtros);

        $query->orderBy('auditorias.created_at', 'desc');

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function applyFilters(Builder $query, array $filtros): void
    {
        if (! empty($filtros['user_id'])) {
            $query->where('auditorias.user_id', (int) $filtros['user_id']);
        }

        if (! empty($filtros['action'])) {
            $this->applyActionFilter($query, $filtros['action']);
        }

        if (! empty($filtros['entity'])) {
            $entityType = $this->mapEntityFilter($filtros['entity']);
            if ($entityType) {
                $query->where('auditorias.entidade_type', $entityType);
            }
        }

        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('auditorias.created_at', '>=', $filtros['data_inicio']);
        }

        if (! empty($filtros['data_fim'])) {
            $query->whereDate('auditorias.created_at', '<=', $filtros['data_fim']);
        }
    }

    private function applyActionFilter(Builder $query, string $action): void
    {
        $acaoMap = [
            'created' => 'criado',
            'updated' => 'atualizado',
            'deleted' => 'removido',
        ];

        if ($action === 'status_changed') {
            $query->where('auditorias.acao', 'atualizado')
                ->where('auditorias.dados', 'like', '%"status"%');
            return;
        }

        if (isset($acaoMap[$action])) {
            $query->where('auditorias.acao', $acaoMap[$action]);
        }
    }

    private function mapRow(object $row): object
    {
        $row->data_acao_formatada = $this->formatDateTime($row->data_acao);
        $row->user_label = $this->formatUser($row->user_nome, $row->user_email);
        $row->perfil_label = $this->formatRole($row->user_role);
        $row->acao_label = $this->getActionLabel($row->acao, $row->dados);
        $row->acao_badge = $this->getActionBadge($row->acao, $row->dados);
        $row->entidade_label = $this->getEntityLabel($row->entidade_type);
        [$before, $after] = $this->extractBeforeAfter($row->acao, $row->dados);
        $row->before_label = $before;
        $row->after_label = $after;
        $row->has_details = $before !== '-' || $after !== '-';

        return $row;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getActionOptions(): array
    {
        return [
            ['value' => 'created', 'label' => 'Created'],
            ['value' => 'updated', 'label' => 'Updated'],
            ['value' => 'deleted', 'label' => 'Deleted'],
            ['value' => 'status_changed', 'label' => 'Status changed'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getEntityOptions(): array
    {
        return [
            ['value' => 'curso', 'label' => 'Curso'],
            ['value' => 'evento', 'label' => 'Evento'],
            ['value' => 'aluno', 'label' => 'Aluno'],
            ['value' => 'inscricao', 'label' => 'Inscrição'],
            ['value' => 'matricula', 'label' => 'Matrícula'],
            ['value' => 'notificacao', 'label' => 'Notificação'],
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

    private function getActionBadge(string $acao, ?string $dados): string
    {
        return match ($acao) {
            'criado' => 'success',
            'removido' => 'danger',
            default => 'warning',
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

    private function mapEntityFilter(string $entity): ?string
    {
        return match ($entity) {
            'curso' => Curso::class,
            'evento' => EventoCurso::class,
            'aluno' => Aluno::class,
            'inscricao' => ListaEspera::class,
            'matricula' => Matricula::class,
            'notificacao' => NotificationLog::class,
            default => null,
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

    private function formatUser(?string $nome, ?string $email): string
    {
        if (! $nome && ! $email) {
            return '-';
        }

        if ($nome && $email) {
            return "{$nome} ({$email})";
        }

        return $nome ?: $email ?: '-';
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
     * @param  array<string, mixed>  $filtros
     */
    private function resolvePerPage(array $filtros): int
    {
        $perPage = (int) ($filtros['per_page'] ?? 15);

        return in_array($perPage, [15, 25, 50], true) ? $perPage : 15;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function getPerPageOptions(): array
    {
        return [
            ['value' => 15, 'label' => '15'],
            ['value' => 25, 'label' => '25'],
            ['value' => 50, 'label' => '50'],
        ];
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
