@php
    $categoria = $categoria ?? null;
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-admin.input
            id="nome"
            name="nome"
            label="Nome"
            :value="$categoria->nome ?? ''"
            required
            wrapper-class="md:col-span-2"
        />
    </div>

    <x-admin.checkbox
        name="ativo"
        label="Ativo"
        :checked="$categoria->ativo ?? true"
    />
</div>
