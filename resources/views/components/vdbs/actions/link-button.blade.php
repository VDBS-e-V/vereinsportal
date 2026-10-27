@props(['href', 'variant' => 'arrow', 'external' => false])

{{--
    Link im Button-Stil (Paket "actions"), Vorbild: FU / Berlin.
    variant: arrow  dunkler Button mit Pfeil
             line   Rahmen/Linie unten, Pfeil links
             all    "Alle anzeigen"-Textlink mit Pfeil
    external: öffnet in neuem Tab, mit Hinweis für Screenreader
--}}

@php
    $variant = in_array($variant, ['arrow', 'line', 'all'], true) ? $variant : 'arrow';
@endphp

<a {{ $attributes->class(['vdbs-link-button', "vdbs-link-button--{$variant}"]) }} href="{{ $href }}" @if ($external) target="_blank" rel="noopener noreferrer" @endif>
    <x-vdbs.icon class="vdbs-link-button__icon" :name="$external ? 'external-link' : 'arrow-right'" :size="18" />
    <span class="vdbs-link-button__label">{{ $slot }}</span>
    @if ($external) <span class="vdbs-sr-only">(öffnet in neuem Tab)</span> @endif
</a>
