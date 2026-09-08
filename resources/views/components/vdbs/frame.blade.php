@props([
    'width' => 'normal',
    'gutter' => 'both',
])

@php
    $widthClass = match ($width) {
        'small' => 'layout-frame--small',
        'normal' => 'layout-frame--normal',
        'wide' => 'layout-frame--wide',
        'full' => 'layout-frame--full',
        default => throw new \InvalidArgumentException(
            "Unknown VDBS frame width [{$width}].",
        ),
    };

    $gutterClass = match ($gutter) {
        'both' => 'layout-frame--gutter',
        'none' => 'layout-frame--flush',
        'start' => 'layout-frame--gutter-start',
        'end' => 'layout-frame--gutter-end',
        default => throw new \InvalidArgumentException(
            "Unknown VDBS frame gutter [{$gutter}].",
        ),
    };
@endphp

<div {{ $attributes->class([
    'layout-frame',
    $widthClass,
    $gutterClass,
]) }}>
    {{ $slot }}
</div>
