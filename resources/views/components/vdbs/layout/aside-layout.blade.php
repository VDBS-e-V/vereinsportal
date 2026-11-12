@props([
    'variant' => 'image',
    'side' => 'end',
    'size' => 'm',
    'mainWidth' => 'fill',
    'mobile' => null,
    'sticky' => false,
    'asideLabel' => null,
])

{{--
    Inhalt mit Bild, Fläche oder Seitenleiste daneben (Paket "layout"). Für Login, Anmeldung, Formulare,
    Detailseiten mit Navigation.

    variant     image    Bild füllt die Spalte (Slot "aside": <x-vdbs.media.image> mit Quelle)
                panel    Fläche mit Innenabstand (Hinweise, Hilfe)
                sidebar  Seitenleiste (Navigation, Infos); mit sticky bleibt sie beim Scrollen stehen
    side        end (rechts) | start (links)
    size        s (16 rem) | m (24 rem) | l (32 rem) | half (zwei gleich breite Spalten)
    main-width  fill | narrow | medium | default   Breite des Inhalts in seiner Spalte (zentriert)
    mobile      above | below | hidden   Position am Handy; Standard: image hidden, sonst below
    aside-label Beschriftung der Seitenleiste (aria-label), nur bei sidebar und panel

    Slots: Standard (Inhalt), aside (Bild, Fläche oder Seitenleiste)
--}}

@php
    $variant = in_array($variant, ['image', 'panel', 'sidebar'], true) ? $variant : 'image';
    $side = $side === 'start' ? 'start' : 'end';
    $size = in_array($size, ['s', 'm', 'l', 'half'], true) ? $size : 'm';
    $mainWidth = in_array($mainWidth, ['fill', 'narrow', 'medium', 'default'], true) ? $mainWidth : 'fill';
    $mobile = in_array($mobile, ['above', 'below', 'hidden'], true) ? $mobile : ($variant === 'image' ? 'hidden' : 'below');
    $asideTag = $variant === 'image' ? 'div' : 'aside';
    $contentClass = 'vdbs-aside-layout__content--'.$mainWidth;
@endphp

<div {{ $attributes->class(['vdbs-aside-layout', "vdbs-aside-layout--{$variant}", "vdbs-aside-layout--side-{$side}", "vdbs-aside-layout--size-{$size}", "vdbs-aside-layout--mobile-{$mobile}", 'vdbs-aside-layout--sticky' => $sticky]) }}>
    <div class="vdbs-aside-layout__main">
        <div class="vdbs-aside-layout__content {{ $contentClass }}">{{ $slot }}</div>
    </div>

    @isset($aside)
        <{{ $asideTag }} class="vdbs-aside-layout__aside" @if ($asideTag === 'aside' && $asideLabel) aria-label="{{ $asideLabel }}" @endif>{{ $aside }}</{{ $asideTag }}>
    @endisset
</div>
