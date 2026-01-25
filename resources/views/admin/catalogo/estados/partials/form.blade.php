@php
    $estado = $estado ?? null;
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-admin.input
            id="nome"
            name="nome"
            label="Nome"
            :value="$estado->nome ?? ''"
            required
        />
        <x-admin.input
            id="uf"
            name="uf"
            label="UF"
            :value="$estado->uf ?? ''"
            maxlength="2"
            required
        />
    </div>

    <x-admin.checkbox
        name="ativo"
        label="Ativo"
        :checked="$estado->ativo ?? true"
    />
</div>
