@php
    $municipio = $municipio ?? null;
    $estadoOptions = $estados->map(fn ($estado) => [
        'value' => $estado->id,
        'label' => "{$estado->nome} ({$estado->uf})",
    ])->all();
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-admin.select
            id="estado_id"
            name="estado_id"
            label="Estado (UF)"
            :options="$estadoOptions"
            :selected="$municipio->estado_id ?? null"
            placeholder="Selecione"
            required
        />
        <x-admin.input
            id="nome"
            name="nome"
            label="Nome"
            :value="$municipio->nome ?? ''"
            required
        />
    </div>

    <x-admin.checkbox
        name="ativo"
        label="Ativo"
        :checked="$municipio->ativo ?? true"
    />
</div>
