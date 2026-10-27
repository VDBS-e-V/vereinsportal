@props(['src', 'alt' => null, 'caption' => null, 'credit' => null, 'ratio' => 'original', 'focus' => null, 'creditPlacement' => 'below'])

{{--
    Bild mit Unterschrift und Quelle (Paket "media").
    credit-placement below: Unterschrift und "Bildquelle: …" unter dem Bild (Vorbild FU)
                     edge:  Quelle als senkrechtes Etikett am Bildrand, Unterschrift darunter
    Alle anderen Eigenschaften wie <x-vdbs.media.image>.
--}}

@php
    $creditPlacement = in_array($creditPlacement, ['below', 'edge'], true) ? $creditPlacement : 'below';
    $source = filled($credit) ? \App\Support\Media\ImageSource::fromCredit($credit) : \App\Support\Media\ImageManifest::make()->find($src);
@endphp

<figure {{ $attributes->class(['vdbs-figure', "vdbs-figure--credit-{$creditPlacement}"]) }}>
    <x-vdbs.media.image :src="$src" :alt="$alt" :credit="$credit" :ratio="$ratio" :focus="$focus" :credit-placement="$creditPlacement === 'edge' ? 'edge' : 'none'" />

    @if ($caption || $creditPlacement === 'below')
        <figcaption class="vdbs-figure__caption">
            @if ($caption) <span class="vdbs-figure__text">{{ $caption }}</span> @endif
            @if ($creditPlacement === 'below') <x-vdbs.media.image-credit :source="$source" placement="below" /> @endif
        </figcaption>
    @endif
</figure>
