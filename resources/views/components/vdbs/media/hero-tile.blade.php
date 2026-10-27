@props(['title', 'href', 'image' => null, 'imageAlt' => '', 'credit' => null])

{{-- Bildkachel im Hero (Variante panel, Slot "aside"): Bild, Titel und ein Link über die ganze Kachel. --}}
<article {{ $attributes->class('vdbs-hero-tile') }}>
    @if ($image)
        <img class="vdbs-hero-tile__image" src="{{ $image }}" alt="{{ $imageAlt }}">
    @endif

    <h2 class="vdbs-hero-tile__title">
        <a class="vdbs-hero-tile__link" href="{{ $href }}">{{ $title }}</a>
    </h2>

    @if ($credit)
        <p class="vdbs-hero-tile__credit">{{ $credit }}</p>
    @endif
</article>
