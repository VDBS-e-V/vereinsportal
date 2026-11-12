@props(['number' => 1, 'label' => null, 'numbered' => true])

{{--
    Gruppen-Marke (Paket "data"): Form und Farbe einer der sechs Gruppen.
    numbered  true: mit der Nummer der Gruppe (Styleguide, Gruppenübersicht); false: nur Form und Farbe, ohne Nummer
              und ohne eigene Beschriftung (dekorativ, z. B. vor dem Namen eines Bereichs)
--}}

@php
    $number = (($number - 1) % 6 + 6) % 6 + 1;
    $names = [1 => 'Kreis, Hellblau', 2 => 'Raute, Gelb', 3 => 'Quadrat, Petrol', 4 => 'Stern, Rosa', 5 => 'Dreieck, Rot', 6 => 'Sechseck, Orange'];
@endphp

@if ($numbered)
    <span {{ $attributes->class(['vdbs-group-mark', "vdbs-group-mark--{$number}"]) }} role="img" aria-label="{{ $label ?? 'Gruppe '.$number.' ('.$names[$number].')' }}"><span aria-hidden="true">{{ $number }}</span></span>
@else
    <span {{ $attributes->class(['vdbs-group-mark', "vdbs-group-mark--{$number}"]) }} aria-hidden="true"></span>
@endif
