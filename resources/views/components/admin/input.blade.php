@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'hint' => null,
    'wrapperClass' => '',
])

<div class="flex flex-col gap-2 {{ $wrapperClass }}">
    <label for="{{ $id }}" class="text-sm font-semibold text-[var(--content-text)]">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40 @error($name) border-red-500 @enderror"
    >
    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
