@props(['variant' => 'text', 'lines' => 3, 'label' => null])

{{--
    Platzhalter während des Ladens (Paket "feedback"), z. B. Livewire-Inhalte oder nachgeladene Listen.
    variant  text (mehrere Zeilen, die letzte kürzer) | title | avatar | card | block
    lines    Zeilen bei variant text
    label    gesetzt: der Platzhalter meldet sich einmal als role="status" mit diesem Text ("Lädt …").
             Ohne label ist er reine Zierde (aria-hidden), z. B. mehrere Platzhalter in einer Gruppe.
    Die Animation ruht bei "weniger Bewegung".
--}}

@php
    $variant = in_array($variant, ['text', 'title', 'avatar', 'card', 'block'], true) ? $variant : 'text';
    $lines = max(1, min(12, (int) $lines));
@endphp

<div {{ $attributes->class(['vdbs-skeleton', "vdbs-skeleton--{$variant}"]) }} @if ($label) role="status" @else aria-hidden="true" @endif>
    @if ($label) <span class="vdbs-sr-only">{{ $label }}</span> @endif

    @if ($variant === 'text')
        @for ($i = 1; $i <= $lines; $i++)
            <span class="vdbs-skeleton__line" @if ($i === $lines && $lines > 1) data-last @endif></span>
        @endfor
    @elseif ($variant === 'card')
        <span class="vdbs-skeleton__block"></span>
        <span class="vdbs-skeleton__line vdbs-skeleton__line--title"></span>
        <span class="vdbs-skeleton__line"></span>
        <span class="vdbs-skeleton__line" data-last></span>
    @else
        <span class="vdbs-skeleton__shape"></span>
    @endif
</div>
