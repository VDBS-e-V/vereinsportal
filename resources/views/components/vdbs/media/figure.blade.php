@props(['src', 'alt' => null, 'caption' => null, 'credit' => null, 'ratio' => 'original', 'focus' => null, 'creditPlacement' => 'below', 'creditOverflow' => 'wrap', 'creditPrefix' => 'Bildquelle', 'creditTone' => 'dark', 'creditSize' => 's', 'creditLicense' => false])

{{--
    Bild mit Unterschrift und Quelle (Paket "media").
    credit-placement below: Unterschrift und "Bildquelle: …" unter dem Bild (Vorbild FU)
                     edge, edge-left, bottom, corner, hover, toggle: Quelle im Bild, Unterschrift darunter
    Alle anderen Eigenschaften wie <x-vdbs.media.image>.
--}}

@php
    $creditPlacement = in_array($creditPlacement, ['below', 'edge', 'edge-left', 'bottom', 'corner', 'hover', 'toggle'], true) ? $creditPlacement : 'below';
    $source = filled($credit) ? \App\Support\Media\ImageSource::fromCredit($credit) : \App\Support\Media\ImageManifest::make()->find($src);
@endphp

<figure {{ $attributes->class(['vdbs-figure', "vdbs-figure--credit-{$creditPlacement}"]) }}>
    <x-vdbs.media.image :src="$src" :alt="$alt" :credit="$credit" :ratio="$ratio" :focus="$focus" :credit-placement="$creditPlacement === 'below' ? 'none' : $creditPlacement" :credit-overflow="$creditOverflow" :credit-prefix="$creditPrefix" :credit-tone="$creditTone" :credit-size="$creditSize" :credit-license="$creditLicense" />

    @if ($caption || $creditPlacement === 'below')
        <figcaption class="vdbs-figure__caption">
            @if ($caption) <span class="vdbs-figure__text">{{ $caption }}</span> @endif
            @if ($creditPlacement === 'below') <x-vdbs.media.image-credit :source="$source" placement="below" :overflow="$creditOverflow" :prefix="$creditPrefix" :size="$creditSize" :license="$creditLicense" /> @endif
        </figcaption>
    @endif
</figure>
