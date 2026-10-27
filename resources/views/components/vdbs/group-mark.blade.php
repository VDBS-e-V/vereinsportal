@props([
    'number',
    'label' => null,
])

@php
    $number = (int) $number;
    $number = $number >= 1 && $number <= 6 ? $number : 1;
    $ariaLabel = $label !== null
        ? 'Gruppe ' . $number . ' — ' . $label
        : 'Gruppe ' . $number;
@endphp

<span
    {{ $attributes->class([
        'group-mark',
        'vdbs-group-mark',
        'group-mark--' . $number,
        'vdbs-group-mark--' . $number,
    ]) }}
    role="img"
    aria-label="{{ $ariaLabel }}"
>
    {{ $number }}
</span>
