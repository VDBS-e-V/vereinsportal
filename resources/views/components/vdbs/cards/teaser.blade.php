@props([
    'title',
    'href',
    'text' => null,
    'image' => null,
    'imageAlt' => '',
    'credit' => null,
    'category' => null,
    'level' => 3,
])

{{--
    Teaser (Paket "cards"): Bild, Kategorie, Titel, Anreißer, Pfeil.
    Der Titel ist der einzige Link; die ganze Karte ist per CSS (::after) klickbar.
--}}

@php
    $tag = 'h'.max(2, min(5, (int) $level));
@endphp

<article {{ $attributes->class('vdbs-teaser') }}>
    @if ($image)
        <figure class="vdbs-teaser__media">
            <img class="vdbs-teaser__image" src="{{ $image }}" alt="{{ $imageAlt }}">
            @if ($credit)
                <figcaption class="vdbs-teaser__credit">{{ $credit }}</figcaption>
            @endif
        </figure>
    @endif

    <div class="vdbs-teaser__body">
        <{{ $tag }} class="vdbs-teaser__title">
            <a class="vdbs-teaser__link" href="{{ $href }}">{{ $title }}</a>
        </{{ $tag }}>

        @if ($text)
            <p class="vdbs-teaser__text">{{ $text }}</p>
        @endif

        <p class="vdbs-teaser__footer">
            @if ($category)
                <span class="vdbs-teaser__category">{{ $category }}</span>
            @endif
            <span class="vdbs-teaser__arrow" aria-hidden="true"><x-vdbs.icon name="arrow-right" :size="18" /></span>
        </p>
    </div>
</article>
