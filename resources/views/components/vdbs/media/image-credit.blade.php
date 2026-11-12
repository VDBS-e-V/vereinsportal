@props([
    'source',
    'placement' => 'below',
    'overflow' => 'wrap',
    'prefix' => 'Bildquelle',
    'tone' => 'dark',
    'size' => 's',
    'license' => false,
])

{{--
    Quellenangabe eines Bildes (Paket "media").

    $source    App\Support\Media\ImageSource (oder null: zeigt den Hinweis "Quelle fehlt")

    placement  below      Zeile unter dem Bild (Vorbild FU: Unterschrift)
               edge       senkrechtes Etikett am rechten Bildrand (Vorbild FU: Hero)
               edge-left  wie edge, am linken Rand
               bottom     Streifen über die Bildbreite am unteren Rand
               corner     kleines Etikett in der rechten unteren Ecke
               hover      Streifen unten, sichtbar bei Hover/Fokus (auf Touch-Geräten immer sichtbar)
               toggle     Knopf "©" in der Ecke, öffnet die Quelle (details, ohne JavaScript)
               Am Handy werden edge und edge-left zu einem waagerechten Streifen unten.

    overflow   wrap      zu lange Quelle bricht um (Standard)
               truncate  einzeilig mit Auslassungspunkten
               expand    wie truncate, bei Hover/Fokus wird der volle Text gezeigt (nur waagerechte Formen)

    prefix     Text vor dem Namen, z. B. "Bildquelle" (Standard), "Foto", "Quelle"; "" = ohne
    tone       dark | light | brand   Farbe der Etiketten (nicht bei below)
    size       s | m
    license    true: hängt die Lizenz an ("Name, Quelle · Lizenz")
--}}

@php
    $placement = in_array($placement, ['below', 'edge', 'edge-left', 'bottom', 'corner', 'hover', 'toggle'], true) ? $placement : 'below';
    $overflow = in_array($overflow, ['wrap', 'truncate', 'expand'], true) ? $overflow : 'wrap';
    $tone = in_array($tone, ['dark', 'light', 'brand'], true) ? $tone : 'dark';
    $size = $size === 'm' ? 'm' : 's';
    $prefix = trim((string) $prefix);

    $label = $source?->label();
    $text = filled($label) ? $label.($license && filled($source->license) ? ' · '.$source->license : '') : 'fehlt';

    $name = e($text);
    $nameHtml = filled($label) && filled($source->url)
        ? '<a class="vdbs-image-credit__name" href="'.e($source->url).'" target="_blank" rel="noopener noreferrer">'.$name.'<span class="vdbs-sr-only"> (öffnet in neuem Tab)</span></a>'
        : '<span class="vdbs-image-credit__name">'.$name.'</span>';
    $inner = new \Illuminate\Support\HtmlString(($prefix !== '' ? '<span class="vdbs-image-credit__prefix">'.e($prefix).':</span> ' : '').$nameHtml);

    $classes = [
        'vdbs-image-credit',
        "vdbs-image-credit--{$placement}",
        "vdbs-image-credit--tone-{$tone}",
        "vdbs-image-credit--size-{$size}",
        "vdbs-image-credit--{$overflow}" => $overflow !== 'wrap',
        'vdbs-image-credit--missing' => blank($label),
    ];
@endphp

@if ($placement === 'toggle')
    <details {{ $attributes->class($classes) }}>
        <summary class="vdbs-image-credit__toggle" aria-label="{{ $prefix !== '' ? $prefix : 'Bildquelle' }} anzeigen"><span aria-hidden="true">©</span></summary>
        <div class="vdbs-image-credit__panel">{{ $inner }}</div>
    </details>
@else
    <small {{ $attributes->class($classes) }}>{{ $inner }}</small>
@endif
