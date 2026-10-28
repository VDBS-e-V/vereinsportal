@props(['columns' => 2])

{{--
    Raster für Felder (Paket "forms"): columns 1 | 2 | 3. Am Handy immer eine Spalte.
    Ein Feld über die ganze Breite: Klasse vdbs-form-grid__full am Element.
--}}

@php
    $columns = in_array((int) $columns, [1, 2, 3], true) ? (int) $columns : 2;
@endphp

<div {{ $attributes->class(['vdbs-form-grid', "vdbs-form-grid--{$columns}"]) }}>{{ $slot }}</div>
