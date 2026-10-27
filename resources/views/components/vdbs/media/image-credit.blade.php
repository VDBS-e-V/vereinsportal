@props(['source', 'placement' => 'below'])

{{--
    Quellenangabe eines Bildes (Paket "media").

    $source   App\Support\Media\ImageSource (oder null: zeigt den Hinweis "Quelle fehlt")
    placement below  Zeile unter dem Bild ("Bildquelle: Name"), Vorbild FU: Unterschrift
              edge   senkrechtes Etikett am rechten Bildrand, Vorbild FU: Hero; am Handy waagerecht unten
--}}

@php
    $placement = in_array($placement, ['below', 'edge'], true) ? $placement : 'below';
    $label = $source?->label();
@endphp

<small {{ $attributes->class(['vdbs-image-credit', "vdbs-image-credit--{$placement}", 'vdbs-image-credit--missing' => blank($label)]) }}>
    <span class="vdbs-image-credit__prefix">Bildquelle:</span>
    @if (blank($label))
        <span class="vdbs-image-credit__name">fehlt</span>
    @elseif ($source->url)
        <a class="vdbs-image-credit__name" href="{{ $source->url }}" target="_blank" rel="noopener noreferrer">{{ $label }}<span class="vdbs-sr-only"> (öffnet in neuem Tab)</span></a>
    @else
        <span class="vdbs-image-credit__name">{{ $label }}</span>
    @endif
</small>
