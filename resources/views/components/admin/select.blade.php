@props([
    'id' => null,
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'wrapperClass' => '',
    'disabled' => false,
])

@php
    $fieldId = $id ?? str_replace(['[', ']'], '_', $name);
@endphp

<div class="flex flex-col gap-2 {{ $wrapperClass }}">
    <label for="{{ $fieldId }}" class="text-sm font-semibold text-[var(--content-text)]">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($disabled) disabled @endif
        class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--card-bg)] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/40 @error($name) border-red-500 @enderror"
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if (trim($slot) !== '')
            {{ $slot }}
        @else
            @foreach ($options as $option)
                <option
                    value="{{ $option['value'] }}"
                    {{ (string) old($name, $selected) === (string) $option['value'] ? 'selected' : '' }}
                >
                    {{ $option['label'] }}
                </option>
            @endforeach
        @endif
    </select>
    @error($name)
        <p class="text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
