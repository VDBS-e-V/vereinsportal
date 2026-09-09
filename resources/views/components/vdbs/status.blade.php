@props([
    'type' => 'neutral',
])

@php
    $allowedTypes = [
        'neutral',
        'info',
        'success',
        'warning',
        'danger',
    ];

    $type = in_array($type, $allowedTypes, true)
        ? $type
        : 'neutral';
@endphp

<span
    {{ $attributes->class([
        'status',
        'vdbs-status',
        'status--'.$type => $type !== 'neutral',
        'vdbs-status--'.$type => $type !== 'neutral',
    ]) }}
>
    {{ $slot }}
</span>
