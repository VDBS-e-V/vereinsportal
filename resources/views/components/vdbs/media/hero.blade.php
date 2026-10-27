@props([
    'title',
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
])

{{--
    Hero (Paket "media").

    Varianten:
      image   Titel über oder neben dem Bild (Vorbild: eigener Entwurf)
      caption Bild mit Bildunterschrift-Leiste und Pfeil-Link (Vorbild: FU Berlin)
      panel   Farbfläche mit Titel/Suche und Bildkacheln im Slot "aside" (Vorbild: Berlin.de);
              ein gesetztes $image wird hier nicht dargestellt

    Slots:
      search   optionales Suchfeld (variant panel)
      actions  Buttons/Links unter dem Titel
      aside    Bildkacheln (<x-vdbs.media.hero-tile>), nur variant panel
      footer   Streifen unter dem Hero, z. B. <x-vdbs.cards.quick-links>

    $imageAlt = '' markiert das Bild als dekorativ.
--}}

@php
    $variant = in_array($variant, ['image', 'caption', 'panel'], true) ? $variant : 'image';
    $align = in_array($align, ['start', 'center'], true) ? $align : 'start';
    $level = max(1, min(3, (int) $level));
    $titleId = 'hero-'.\Illuminate\Support\Str::slug($title).'-title';
    $tag = 'h'.$level;
@endphp

<section {{ $attributes->class(['vdbs-hero', "vdbs-hero--{$variant}", "vdbs-hero--align-{$align}"]) }} aria-labelledby="{{ $titleId }}">
    <div class="vdbs-hero__stage">
        @if ($image)
            <figure class="vdbs-hero__media">
                <img class="vdbs-hero__image" src="{{ $image }}" alt="{{ $imageAlt }}">
                @if ($credit)
                    <figcaption class="vdbs-hero__credit">{{ $credit }}</figcaption>
                @endif
            </figure>
        @endif

        <div class="vdbs-hero__content">
            @if ($eyebrow)
                <p class="vdbs-hero__eyebrow">{{ $eyebrow }}</p>
            @endif

            <{{ $tag }} class="vdbs-hero__title" id="{{ $titleId }}">{{ $title }}</{{ $tag }}>

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
