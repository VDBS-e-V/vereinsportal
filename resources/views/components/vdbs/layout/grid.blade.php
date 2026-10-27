@props(['columns' => 3, 'as' => 'div'])

{{-- Gleichmäßiges Raster (Paket "layout"): columns 2 | 3 | 4. as: div | ul (Kinder dann <li>). --}}

@php
    $columns = in_array((int) $columns, [2, 3, 4], true) ? (int) $columns : 3;
    $as = $as === 'ul' ? 'ul' : 'div';
@endphp

<{{ $as }} {{ $attributes->class(['vdbs-grid', "vdbs-grid--{$columns}"]) }}>{{ $slot }}</{{ $as }}>
