@props([
    'name',
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'h-3 w-3',
        'sm' => 'h-3.5 w-3.5',
        'lg' => 'h-5 w-5',
        'xl' => 'h-6 w-6',
        default => 'h-4 w-4',
    };
@endphp

@switch($name)
    @case('plus')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
        </svg>
        @break

    @case('home')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5L12 4l9 7.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5V20h14v-9.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 20v-5h5v5" />
        </svg>
        @break

    @case('calendar')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v4M16 3v4" />
        </svg>
        @break

    @case('book')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5V5.5z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5V20" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11h8" />
        </svg>
        @break

    @case('chevron-right')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
        </svg>
        @break

    @case('check')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7" />
        </svg>
        @break

    @case('edit')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h4.5L19.75 9.75l-4.5-4.5L4 16.5V21z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5l4 4" />
        </svg>
        @break

    @case('trash')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 7l1 12.5A2.5 2.5 0 0 0 8.5 22h7a2.5 2.5 0 0 0 2.5-2.5L19 7" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1V7" />
        </svg>
        @break

    @case('eye')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12c2.5-5 6.5-8 9.5-8s7 3 9.5 8c-2.5 5-6.5 8-9.5 8s-7-3-9.5-8z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
        </svg>
        @break

    @case('search')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <circle cx="11" cy="11" r="7" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5" />
        </svg>
        @break

    @case('filter')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 12h10" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19h4" />
        </svg>
        @break

    @case('arrow-left')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        @break

    @case('x')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18" />
        </svg>
        @break

    @case('user-plus')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <circle cx="9" cy="8" r="4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 20a6 6 0 0 1 12 0" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 8v6" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 11h6" />
        </svg>
        @break

    @case('download')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10l4 4 4-4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
        </svg>
        @break

    @case('settings')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 1 0 0 8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h2m12 0h2M12 4v2m0 12v2M6.5 6.5l1.4 1.4m8.2 8.2l1.4 1.4m0-11l-1.4 1.4m-8.2 8.2l-1.4 1.4" />
        </svg>
        @break

    @case('lock')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0 1 10 0v3" />
            <rect x="5" y="10" width="14" height="10" rx="2" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v2" />
        </svg>
        @break

    @case('logout')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 4h2v16h-2" />
        </svg>
        @break

    @case('user')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false" {{ $attributes->class([$sizeClass, 'btn-icon-svg']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 0-8 0" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20a8 8 0 0 1 16 0" />
        </svg>
        @break
@endswitch
