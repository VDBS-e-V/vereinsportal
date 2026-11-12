@props(['columns' => 3, 'as' => 'div', 'align' => 'stretch'])

{{-- Gleichmäßiges Raster (Paket "layout"): columns 2 | 3 | 4. as: div | ul (Kinder dann <li>).
    align: stretch (gleich hohe Zeilen) | start (Karten behalten ihre eigene Höhe, z. B. Nachrichten-Teaser). --}}

@php
    $columns = in_array((int) $columns, [2, 3, 4], true) ? (int) $columns : 3;
    $align = $align === 'start' ? 'start' : 'stretch';
    $as = $as === 'ul' ? 'ul' : 'div';
@endphp

<{{ $as }} {{ $attributes->class(['vdbs-grid', "vdbs-grid--{$columns}", 'vdbs-grid--align-start' => $align === 'start']) }}>{{ $slot }}</{{ $as }}>
