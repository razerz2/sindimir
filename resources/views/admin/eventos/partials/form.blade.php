@php
    $evento = $evento ?? null;
    $cursoOptions = $cursos->map(fn ($curso) => [
        'value' => $curso->id,
        'label' => $curso->nome,
    ])->all();
    $turnoOptions = collect($turnos)->map(fn ($turno) => [
        'value' => $turno->value,
        'label' => ucfirst(str_replace('_', ' ', $turno->value)),
    ])->all();
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <x-admin.select
            id="curso_id"
            name="curso_id"
            label="Curso"
            :options="$cursoOptions"
            :selected="$evento->curso_id ?? null"
            placeholder="Selecione"
            required
            wrapper-class="lg:col-span-2"
        />
        <x-admin.input
            id="numero_evento"
            name="numero_evento"
            label="Numero do evento"
            :value="$evento->numero_evento ?? ''"
            required
        />
        <x-admin.input
            id="data_inicio"
            name="data_inicio"
            label="Data de inicio"
            type="date"
            :value="isset($evento) ? $evento->data_inicio->format('Y-m-d') : ''"
            required
        />
        <x-admin.input
            id="data_fim"
            name="data_fim"
            label="Data de fim"
            type="date"
            :value="isset($evento) ? $evento->data_fim->format('Y-m-d') : ''"
            required
        />
        <x-admin.input
            id="carga_horaria"
            name="carga_horaria"
            label="Carga horaria"
            type="number"
            :value="$evento->carga_horaria ?? 0"
            required
        />
        <x-admin.input
            id="municipio"
            name="municipio"
            label="Municipio"
            :value="$evento->municipio ?? ''"
            required
        />
        <x-admin.input
            id="local_realizacao"
            name="local_realizacao"
            label="Local de realizacao"
            :value="$evento->local_realizacao ?? ''"
            required
            wrapper-class="lg:col-span-2"
        />
        <x-admin.select
            id="turno"
            name="turno"
            label="Turno"
            :options="$turnoOptions"
            :selected="$evento->turno?->value ?? null"
            placeholder="Nao informado"
        />
    </div>

    <x-admin.checkbox
        name="ativo"
        label="Ativo"
        :checked="$evento->ativo ?? true"
    />
</div>
