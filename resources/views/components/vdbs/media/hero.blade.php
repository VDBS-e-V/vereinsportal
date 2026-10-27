@props([
    'title',
    'heading' => null,
    'variant' => 'image',
    'eyebrow' => null,
    'lead' => null,
    'image' => null,
    'imageAlt' => '',
    'credit' => null,
    'caption' => null,
    'captionHref' => null,
    'captionLabel' => 'Mehr erfahren',
    'align' => 'start',
    'level' => 1,
    'size' => 'm',
    'bleed' => false,
])

{{--
    Hero (Paket "media").

    Varianten (variant):
      image    Titel auf heller Fläche über dem Bild (eigener Entwurf)
      overlay  Bild über die ganze Fläche, Titel in Weiß unten links auf dunklem Verlauf
      caption  Titel, Bild und Bildunterschrift-Leiste mit Pfeil-Link (Vorbild: FU Berlin)
      split    Text auf Fläche neben dem Bild, 2 Spalten
      panel    Farbfläche mit Titel/Suche und Bildkacheln im Slot "aside" (Vorbild: Berlin.de);
               ein gesetztes $image wird hier nicht dargestellt
      compact  Seitenkopf ohne Bild: Titel, Einleitung, Aktionen auf Fläche
      banner   Flacher Bildstreifen, Titel in Weiß unten links (Vorbild: Berlin.de Bereichsseiten)
      feature  Überschrift, Bild links, dunkle Textfläche rechts mit Aktion; Karten im Slot "footer"
               (Vorbild: Berlin.de Senatsverwaltung)

    Weitere Eigenschaften:
      size     s | m | l   Höhe der Varianten image, overlay und split
      align    start | center
      bleed    true, wenn das Hero in einem Layout mit width="full" über die ganze Seitenbreite läuft;
               der Inhalt bleibt dann auf Container-Breite
      level    Überschriftenebene 1–3 (pro Seite genau ein h1)
      heading  nur feature: Überschrift über dem Bild; der Titel in der Fläche rutscht eine Ebene tiefer

    Slots:
      search   optionales Suchfeld (variant panel)
      actions  Buttons/Links unter dem Titel
      aside    Bildkacheln (<x-vdbs.media.hero-tile>), nur variant panel
      footer   Streifen unter dem Hero, z. B. <x-vdbs.cards.quick-links>

    $imageAlt = '' markiert das Bild als dekorativ.
--}}

@php
    $variant = in_array($variant, ['image', 'overlay', 'caption', 'split', 'panel', 'compact', 'banner', 'feature'], true) ? $variant : 'image';
    $size = in_array($size, ['s', 'm', 'l'], true) ? $size : 'm';
    $align = in_array($align, ['start', 'center'], true) ? $align : 'start';
    $level = max(1, min(3, (int) $level));
    $hasHeading = $variant === 'feature' && filled($heading);
    $titleId = 'hero-'.\Illuminate\Support\Str::slug($hasHeading ? $heading : $title).'-title';
    $tag = 'h'.min(4, $hasHeading ? $level + 1 : $level);
    $headingTag = 'h'.$level;
@endphp

<section {{ $attributes->class(['vdbs-hero', "vdbs-hero--{$variant}", "vdbs-hero--size-{$size}", "vdbs-hero--align-{$align}", 'vdbs-hero--bleed' => $bleed]) }} aria-labelledby="{{ $titleId }}">
    @if ($hasHeading)
        <{{ $headingTag }} class="vdbs-hero__heading" id="{{ $titleId }}">{{ $heading }}</{{ $headingTag }}>
    @endif

    <div class="vdbs-hero__stage">
        @if ($image)
            <figure class="vdbs-hero__media">
                <img class="vdbs-hero__image" src="{{ $image }}" alt="{{ $imageAlt }}">
                @if ($credit)
                    <figcaption class="vdbs-hero__credit">{{ $credit }}</figcaption>
                @endif
            </figure>
        @endif

        <div @class(['vdbs-hero__content', 'vdbs-on-dark' => in_array($variant, ['overlay', 'banner', 'feature'], true), 'vdbs-on-brand' => $variant === 'panel'])>
            @if ($eyebrow)
                <p class="vdbs-hero__eyebrow">{{ $eyebrow }}</p>
            @endif

            <{{ $tag }} class="vdbs-hero__title" @unless ($hasHeading) id="{{ $titleId }}" @endunless>{{ $title }}</{{ $tag }}>

            @if ($lead)
                <p class="vdbs-hero__lead">{{ $lead }}</p>
            @endif

            @isset($search)
                <div class="vdbs-hero__search">{{ $search }}</div>
            @endisset

            @isset($actions)
                <div class="vdbs-hero__actions">{{ $actions }}</div>
            @endisset
        </div>

        @isset($aside)
            <div class="vdbs-hero__aside">{{ $aside }}</div>
        @endisset
    </div>

    @if ($caption)
        <div class="vdbs-hero__caption">
            <p class="vdbs-hero__caption-text">{{ $caption }}</p>
            @if ($captionHref)
                <a class="vdbs-hero__caption-link" href="{{ $captionHref }}">
                    <x-vdbs.icon name="arrow-right" />
                    <span class="vdbs-sr-only">{{ $captionLabel }}: {{ $caption }}</span>
                </a>
            @endif
        </div>
    @endif

    @isset($footer)
        <div class="vdbs-hero__footer">{{ $footer }}</div>
    @endisset
</section>
