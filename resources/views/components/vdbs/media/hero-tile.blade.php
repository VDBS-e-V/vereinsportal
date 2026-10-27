@props(['title', 'href', 'image' => null, 'imageAlt' => '', 'credit' => null])

{{-- Bildkachel im Hero (Variante panel, Slot "aside"): Bild, Titel und ein Link über die ganze Kachel. --}}
<article {{ $attributes->class('vdbs-hero-tile') }}>
    @if ($image)
        <x-vdbs.media.image class="vdbs-hero-tile__image" :src="$image" :alt="$imageAlt" :credit="$credit" credit-placement="edge" />
    @endif

    <h2 class="vdbs-hero-tile__title">
        <a class="vdbs-hero-tile__link" href="{{ $href }}">{{ $title }}</a>
    </h2>

</article>
