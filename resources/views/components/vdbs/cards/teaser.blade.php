@props([
    'title',
    'href',
    'text' => null,
    'meta' => null,
    'image' => null,
    'imageAlt' => '',
    'credit' => null,
    'category' => null,
    'variant' => 'default',
    'tone' => 'surface',
    'ratio' => null,
    'level' => 3,
])

{{--
    Teaser (Paket "cards"): Bild, Titel, Anreißer, Pfeil.
    Der Titel ist der einzige Link; die ganze Karte ist per CSS (::after) klickbar.

    variant  default   Karte mit Schatten, Titel fett, Kategorie neben dem Pfeil
             flat      flache graue Karte, Titel in Normalschrift, Anreißer gedämpft, Pfeilfläche rechts unten
                       (Vorbild: FU Berlin Nachrichten); in einem Raster mit align="start" ergeben sich
                       unterschiedlich hohe Karten
             featured  breit: großes Bild und eine Titelzeile mit Pfeil darunter (Aufmacher)
             compact   kleine Karte für Orte und Einrichtungen: kleines Bild, Titel, meta (z. B. Adresse), Pfeil
    tone     surface | white   white für Karten auf einem grauen Band (<x-vdbs.layout.section variant="band">)
    ratio    original | 16-9 | 3-2 | 4-3 | 1-1 | 21-9   Zuschnitt des Bildes (Standard je Variante)
    meta     gedämpfte Zeile unter dem Titel
    category kleine Zeile über dem Titel (flat, featured, compact) bzw. neben dem Pfeil (default)
--}}

@php
    $variant = in_array($variant, ['default', 'flat', 'featured', 'compact'], true) ? $variant : 'default';
    $tone = $tone === 'white' ? 'white' : 'surface';
    $ratio ??= match ($variant) { 'featured' => '21-9', 'compact' => '3-2', default => '3-2' };
    $tag = 'h'.max(2, min(5, (int) $level));
    $categoryInFooter = $variant === 'default';
@endphp

<article {{ $attributes->class(['vdbs-teaser', "vdbs-teaser--{$variant}", 'vdbs-teaser--white' => $tone === 'white']) }}>
    @if ($image)
        <div class="vdbs-teaser__media">
            <x-vdbs.media.image class="vdbs-teaser__image" :src="$image" :alt="$imageAlt" :credit="$credit" :ratio="$ratio" credit-placement="edge" />
        </div>
    @endif

    <div class="vdbs-teaser__body">
        @if ($category && ! $categoryInFooter)
            <p class="vdbs-teaser__category">{{ $category }}</p>
        @endif

        <{{ $tag }} class="vdbs-teaser__title">
            <a class="vdbs-teaser__link" href="{{ $href }}">{{ $title }}</a>
        </{{ $tag }}>

        @if ($meta)
            <p class="vdbs-teaser__meta">{{ $meta }}</p>
        @endif

        @if ($text)
            <p class="vdbs-teaser__text">{{ $text }}</p>
        @endif

        <p class="vdbs-teaser__footer">
            @if ($category && $categoryInFooter)
                <span class="vdbs-teaser__category">{{ $category }}</span>
            @endif
            <span class="vdbs-teaser__arrow" aria-hidden="true"><x-vdbs.icon name="arrow-right" :size="18" /></span>
        </p>
    </div>
</article>
