@props(['href', 'variant' => 'arrow', 'external' => false])

{{--
    Kurzform des Button-Systems für Links (Paket "actions"):
      arrow  Primär mit Pfeil-Kachel         = button variant primary,   shape arrow
      line   Rahmen mit Linie unten          = button variant secondary, shape line
      all    "Alle anzeigen"-Textlink        = button variant tertiary,  shape link, Pfeil-Icon
    Weitere Eigenschaften (size, on, …) gehen direkt an <x-vdbs.actions.button>.
--}}

@php
    $map = [
        'arrow' => ['variant' => 'primary', 'shape' => 'arrow'],
        'line' => ['variant' => 'secondary', 'shape' => 'line'],
        'all' => ['variant' => 'tertiary', 'shape' => 'link', 'icon' => 'arrow-right'],
    ];
    $config = $map[$variant] ?? $map['arrow'];
@endphp

<x-vdbs.actions.button :href="$href" :external="$external" {{ $attributes->merge($config) }}>{{ $slot }}</x-vdbs.actions.button>
