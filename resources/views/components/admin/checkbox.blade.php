@props([
    'id' => null,
    'name',
    'label',
    'checked' => false,
    'wrapperClass' => '',
])

@php
    $id = $id ?? $name;
@endphp

<div class="flex flex-col gap-2 {{ $wrapperClass }}">
    <label for="{{ $id }}" class="text-sm font-semibold text-[var(--content-text)]">
        {{ $label }}
    </label>
    <label for="{{ $id }}" class="flex w-full items-center gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm font-semibold">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            class="h-4 w-4 rounded border-[var(--border-color)] text-[var(--color-primary)] focus:ring-[var(--color-primary)]/40"
            {{ old($name, $checked) ? 'checked' : '' }}
        >
        <span class="sr-only">{{ $label }}</span>
    </label>
    @error($name)
        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
