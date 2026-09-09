@props([
    'type' => 'info',
    'role' => null,
])

@php
    $allowedTypes = [
        'info',
        'success',
        'warning',
        'danger',
    ];

    $type = in_array($type, $allowedTypes, true)
        ? $type
        : 'info';
@endphp

<div
    {{ $attributes->class([
        'notice',
        'notice--'.$type,
        'vdbs-notice',
        'vdbs-notice--'.$type,
    ]) }}
    @if ($role !== null)
        role="{{ $role }}"
    @endif
>
    {{ $slot }}
</div>
