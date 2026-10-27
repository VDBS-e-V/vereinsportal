@props(['number' => 1, 'label' => null])

@php
    $number = (($number - 1) % 6 + 6) % 6 + 1;
    $names = [1 => 'Kreis, Hellblau', 2 => 'Raute, Gelb', 3 => 'Quadrat, Petrol', 4 => 'Stern, Rosa', 5 => 'Dreieck, Rot', 6 => 'Sechseck, Orange'];
@endphp

<span {{ $attributes->class(['vdbs-group-mark', "vdbs-group-mark--{$number}"]) }} role="img" aria-label="{{ $label ?? 'Gruppe '.$number.' ('.$names[$number].')' }}"><span aria-hidden="true">{{ $number }}</span></span>
