@props(['title', 'href', 'text' => null, 'icon' => null, 'tone' => null, 'level' => 3])

{{--
    Link-Karte (Paket "cards"): Icon-Kachel, Titel, kurzer Text und Pfeil. Der Titel ist der einzige Link, die ganze Karte ist per CSS klickbar.
    tone   1–6 = Gruppenfarbe als Akzent (die Farbe des Bereichs); Schrift bleibt Ink
    Der Standard-Slot nimmt zusätzliche Angaben unter dem Text auf (z. B. ein Badge).
    Mehrere Karten stehen in <x-vdbs.layout.grid :columns="3">.
--}}

@php
    $tone = $tone !== null ? max(1, min(6, (int) $tone)) : null;
    $tag = 'h'.max(2, min(4, (int) $level));
@endphp

<div {{ $attributes->class(['vdbs-link-card']) }} @if ($tone) style="--card-tone: var(--group-{{ $tone }})" @endif>
    @if ($icon)<span class="vdbs-link-card__icon" aria-hidden="true"><x-vdbs.icon :name="$icon" :size="24" /></span>@endif
    <div class="vdbs-link-card__body">
        <{{ $tag }} class="vdbs-link-card__title"><a class="vdbs-link-card__link" href="{{ $href }}">{{ $title }}</a></{{ $tag }}>
        @if ($text)<p class="vdbs-link-card__text">{{ $text }}</p>@endif
        {{ $slot }}
    </div>
    <x-vdbs.icon class="vdbs-link-card__arrow" name="arrow-right" :size="20" aria-hidden="true" />
</div>
