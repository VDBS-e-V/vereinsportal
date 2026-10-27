@props(['width' => 'default', 'as' => 'div'])

{{--
    Container (Paket "layout"): begrenzt die Inhaltsbreite und setzt den Seitenrand.

    width: narrow  40rem  Login, Formulare, Lesetext
           medium  56rem  Artikel, Konto
           default 72rem  Standardseiten
           wide    90rem  Tabellen, Verwaltung
           full    ohne Begrenzung (nur Seitenrand)
--}}

@php
    $width = in_array($width, ['narrow', 'medium', 'default', 'wide', 'full'], true) ? $width : 'default';
    $as = in_array($as, ['div', 'section', 'article', 'header', 'footer'], true) ? $as : 'div';
@endphp

<{{ $as }} {{ $attributes->class(['vdbs-container', "vdbs-container--{$width}"]) }}>{{ $slot }}</{{ $as }}>
