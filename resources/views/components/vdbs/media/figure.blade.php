@props(['src', 'alt' => '', 'caption' => null, 'credit' => null])

{{-- Bild mit Unterschrift und Bildnachweis (Paket "media"). alt="" = dekorativ. --}}
<figure {{ $attributes->class('vdbs-figure') }}>
    <img class="vdbs-figure__image" src="{{ $src }}" alt="{{ $alt }}">

    @if ($caption || $credit)
        <figcaption class="vdbs-figure__caption">
            @if ($caption) <span class="vdbs-figure__text">{{ $caption }}</span> @endif
            @if ($credit) <span class="vdbs-figure__credit">{{ $credit }}</span> @endif
        </figcaption>
    @endif
</figure>
