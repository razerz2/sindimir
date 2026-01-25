@php
    $curso = $curso ?? null;
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-admin.input
            id="nome"
            name="nome"
            label="Nome"
            :value="$curso->nome ?? ''"
            required
            wrapper-class="md:col-span-2"
        />
        <x-admin.textarea
            id="descricao"
            name="descricao"
            label="Descricao"
            :value="$curso->descricao ?? ''"
            rows="4"
            wrapper-class="md:col-span-2"
        />
        <x-admin.select
            id="categoria_id"
            name="categoria_id"
            label="Categoria"
            :options="$categorias->map(fn ($categoria) => ['value' => $categoria->id, 'label' => $categoria->nome])->all()"
            :selected="$curso->categoria_id ?? null"
            placeholder="Selecione uma categoria"
            required
        />
        <x-admin.input
            id="validade"
            name="validade"
            label="Validade"
            type="date"
            :value="isset($curso) && $curso->validade ? $curso->validade->format('Y-m-d') : ''"
        />
        <x-admin.input
            id="limite_vagas"
            name="limite_vagas"
            label="Limite de vagas"
            type="number"
            :value="$curso->limite_vagas ?? 0"
            required
        />
    </div>

    <x-admin.checkbox
        name="ativo"
        label="Ativo"
        :checked="$curso->ativo ?? true"
    />
</div>
