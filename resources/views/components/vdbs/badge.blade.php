@props([
    'tone' => 'neutral',
])

@php
    $tone = in_array($tone, ['neutral', 'accent'], true)
        ? $tone
        : 'neutral';
@endphp

<span
    {{ $attributes->class([
        'badge',
        'vdbs-badge',
        'badge--accent' => $tone === 'accent',
        'vdbs-badge--accent' => $tone === 'accent',
    ]) }}
>
    {{ $slot }}
</span>
