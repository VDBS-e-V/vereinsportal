@props([
    'src',
    'alt' => null,
    'credit' => null,
    'ratio' => 'original',
    'focus' => null,
    'creditPlacement' => 'below',
    'creditOverflow' => 'wrap',
    'loading' => 'lazy',
])

{{--
    Bild mit Pflicht-Quelle (Paket "media").

    src              URL des Bildes
    alt              Beschreibung für Screenreader. Pflicht; alt="" markiert das Bild bewusst als dekorativ
    credit           optional: Freitext-Quelle (überschreibt das Manifest), z. B. credit="Bernd Wannenmacher"
                     Ohne credit wird die Quelle über den Dateinamen im Manifest (sources.json) gesucht.
    ratio            original | 16-9 | 3-2 | 4-3 | 1-1 | 21-9
    focus            Fokuspunkt beim Zuschnitt, z. B. "30% 20%"
    credit-placement below | edge | none   (none: Quelle zeigt der umgebende Baustein, z. B. <x-vdbs.media.figure>)
    credit-overflow  wrap | truncate   lange Quelle in weitere Zeile/Spalte umbrechen (Standard) oder abschneiden
    loading          lazy | eager

    Fehlt die Quelle oder der Alt-Text: im strikten Modus (config images.strict, Standard außerhalb der
    Produktion) eine Exception; in Produktion ein sichtbarer Hinweis "Quelle fehlt" und ein Log-Eintrag.
--}}

@php
    $manifestSource = \App\Support\Media\ImageManifest::make()->find($src);
    $source = filled($credit) ? \App\Support\Media\ImageSource::fromCredit($credit) : $manifestSource;
    $strict = (bool) config('images.strict', false);

    if (($source === null || ! $source->isComplete())) {
        if ($strict) { throw \App\Support\Media\MissingImageSourceException::forSrc($src); }
        \Illuminate\Support\Facades\Log::error("Bild ohne Quelle: {$src}");
        $source = null;
    }

    if ($alt === null) {
        if ($strict) { throw \App\Support\Media\MissingImageSourceException::missingAlt($src); }
        \Illuminate\Support\Facades\Log::error("Bild ohne Alt-Text: {$src}");
        $alt = '';
    }

    $ratio = in_array($ratio, ['original', '16-9', '3-2', '4-3', '1-1', '21-9'], true) ? $ratio : 'original';
    $creditPlacement = in_array($creditPlacement, ['below', 'edge', 'none'], true) ? $creditPlacement : 'below';
    $focus = is_string($focus) && preg_match('/^\d{1,3}% \d{1,3}%$/', $focus) ? $focus : null;
    $loading = $loading === 'eager' ? 'eager' : 'lazy';
@endphp

<div {{ $attributes->class(['vdbs-image', "vdbs-image--ratio-{$ratio}", "vdbs-image--credit-{$creditPlacement}", 'vdbs-image--placeholder' => $source?->placeholder]) }} @if ($source?->placeholder) data-placeholder="true" @endif>
    <img class="vdbs-image__img" src="{{ $src }}" alt="{{ $alt }}" loading="{{ $loading }}" @if ($focus) style="object-position: {{ $focus }}" @endif>

    @if ($creditPlacement !== 'none')
        <x-vdbs.media.image-credit :source="$source" :placement="$creditPlacement" :overflow="$creditOverflow" />
    @endif
</div>
