@props(['tone' => 'neutral', 'category' => null])

@php
    $category = $category !== null ? max(1, min(6, (int) $category)) : null;
    $tone = in_array($tone, ['neutral', 'success', 'warning', 'error'], true) ? $tone : 'neutral';
@endphp

<span {{ $attributes->class([
    'vdbs-badge',
    "vdbs-badge--{$tone}" => $category === null && $tone !== 'neutral',
    "vdbs-badge--cat-{$category}" => $category !== null,
]) }}>{{ $slot }}</span>
