@props([
    'items' => [],
])

@if (! empty($items))
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <ol class="page-breadcrumb__list">
            @foreach ($items as $item)
                @php
                    $label = (string) ($item['label'] ?? '');
                    $href = $item['href'] ?? null;
                    $icon = $item['icon'] ?? null;
                    $current = (bool) ($item['current'] ?? false);
                @endphp
                @if ($label === '')
                    @continue
                @endif

                @if (! $loop->first)
                    <li class="page-breadcrumb__item" aria-hidden="true">
                        <x-admin.icon name="chevron-right" size="xs" />
                    </li>
                @endif

                <li class="page-breadcrumb__item">
                    @if ($href && ! $current)
                        <a class="page-breadcrumb__link" href="{{ $href }}">
                            @if ($icon)
                                <x-admin.icon :name="$icon" size="xs" />
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @else
                        <span class="page-breadcrumb__current" @if ($current) aria-current="page" @endif>
                            @if ($icon)
                                <x-admin.icon :name="$icon" size="xs" />
                            @endif
                            <span>{{ $label }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
