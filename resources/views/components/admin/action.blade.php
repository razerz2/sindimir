@props([
    'as' => 'button',
    'variant' => 'ghost',
    'icon' => null,
    'iconSize' => 'md',
    'iconPosition' => 'left',
])

@php
    $tag = in_array($as, ['a', 'button'], true) ? $as : 'button';
    $variantClass = match ($variant) {
        'primary' => 'btn-primary',
        'danger' => 'btn-danger',
        default => 'btn-ghost',
    };
    $classes = ['btn', $variantClass];

    if ($icon) {
        $classes[] = 'btn-icon';
    }
@endphp

@if ($tag === 'a')
    <a {{ $attributes->class($classes) }}>
        @if ($icon && $iconPosition !== 'right')
            <x-admin.icon :name="$icon" :size="$iconSize" />
        @endif
        @if (trim((string) $slot) !== '')
            <span>{{ $slot }}</span>
        @endif
        @if ($icon && $iconPosition === 'right')
            <x-admin.icon :name="$icon" :size="$iconSize" />
        @endif
    </a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $attributes->get('type', 'button')]) }}>
        @if ($icon && $iconPosition !== 'right')
            <x-admin.icon :name="$icon" :size="$iconSize" />
        @endif
        @if (trim((string) $slot) !== '')
            <span>{{ $slot }}</span>
        @endif
        @if ($icon && $iconPosition === 'right')
            <x-admin.icon :name="$icon" :size="$iconSize" />
        @endif
    </button>
@endif
