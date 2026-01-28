@extends('aluno.layouts.app')

@section('title', 'Histórico')
@section('subtitle', 'Acompanhe suas inscrições, confirmações e listas de espera.')

@section('content')
    @if (session('status'))
        <div class="alert alert-card">
            <p>{{ session('status') }}</p>
        </div>
    @endif

    <h3 class="section-title">Matrículas</h3>
    @if ($matriculas->isEmpty())
        <div class="card">
            <p>Você ainda não possui matrículas registradas.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Evento</th>
                        <th>Periodo</th>
                        <th>Status</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matriculas as $matricula)
                        @php
                            $evento = $matricula->eventoCurso;
                            $curso = $evento?->curso;
                            $statusClass = match ($matricula->status?->value) {
                                'confirmada' => 'success',
                                'pendente' => 'warning',
                                'cancelada' => 'neutral',
                                'expirada' => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr>
                            <td>{{ $curso?->nome ?? 'Curso' }}</td>
                            <td>{{ $evento?->numero_evento ?? '-' }}</td>
                            <td>
                                @if ($evento?->data_inicio)
                                    {{ $evento->data_inicio->format('d/m/Y') }}
                                    @if ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio))
                                        a {{ $evento->data_fim->format('d/m/Y') }}
                                    @endif
                                @else
                                    Data nao informada
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($matricula->status?->value ?? 'matricula') }}
                                </span>
                            </td>
                            <td>{{ $matricula->created_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h3 class="section-title">Lista de espera</h3>
    @if ($listaEspera->isEmpty())
        <div class="card">
            <p>Você não possui inscrições em lista de espera.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Evento</th>
                        <th>Periodo</th>
                        <th>Status</th>
                        <th>Posição</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($listaEspera as $item)
                        @php
                            $evento = $item->eventoCurso;
                            $curso = $evento?->curso;
                            $statusClass = $item->status?->value === 'chamado' ? 'info' : 'warning';
                        @endphp
                        <tr>
                            <td>{{ $curso?->nome ?? 'Curso' }}</td>
                            <td>{{ $evento?->numero_evento ?? '-' }}</td>
                            <td>
                                @if ($evento?->data_inicio)
                                    {{ $evento->data_inicio->format('d/m/Y') }}
                                    @if ($evento->data_fim && $evento->data_fim->ne($evento->data_inicio))
                                        a {{ $evento->data_fim->format('d/m/Y') }}
                                    @endif
                                @else
                                    Data nao informada
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($item->status?->value ?? 'aguardando') }}
                                </span>
                            </td>
                            <td>{{ $item->posicao ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
