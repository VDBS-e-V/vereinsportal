@props([
    'title',
    'variant' => 'side',
    'tone' => 'none',
    'eyebrow' => null,
    'reverse' => false,
    'image' => null,
    'imageAlt' => '',
    'credit' => null,
    'caption' => null,
    'creditPlacement' => null,
    'ratio' => null,
    'mediaSize' => 'l',
    'level' => 2,
    'id' => null,
])

{{--
    Inhaltsblock mit Text, Bild und Aktionen (Paket "content").

    Varianten (variant):
      side     Text links, großes Bild rechts mit Unterschrift und Bildquelle darunter (Vorbild: FU Berlin
               "Forschung", "Studium"); mit tone="surface" als graues Band über die volle Breite
      overlap  Farbfläche mit Text überlappt das Bild seitlich (Vorbild: Oodi "Mitä Oodissa voi tehdä?")
      offset   Große Farbfläche mit Text, das Bild ragt links über den Rand hinaus (Vorbild: VÖBB)
      links    Überschrift und Text links, Aktionen als Spalte rechts (Vorbild: FU "Haltung & Verantwortung");
               ohne Bild

    tone     none | surface | soft | dark | brand
             side/links: Farbe des Bandes; overlap/offset: Farbe der Textfläche
    reverse  Bild und Text vertauschen
    eyebrow  kleine Zeile über der Überschrift
    image, imageAlt, credit, caption, creditPlacement, ratio  wie <x-vdbs.media.figure>; die Bildquelle ist
             Pflicht, imageAlt="" markiert das Bild als dekorativ
    mediaSize  m | l   (nur side) Bild gleich breit wie der Text oder doppelt so breit
    level    Überschriftenebene 2–4

    Slots: Standard (Text), actions (Buttons/Links; bei variant links als Spalte rechts)
--}}

@php
    $variant = in_array($variant, ['side', 'overlap', 'offset', 'links'], true) ? $variant : 'side';
    $tone = in_array($tone, ['none', 'surface', 'soft', 'dark', 'brand'], true) ? $tone : 'none';
    $mediaSize = in_array($mediaSize, ['m', 'l'], true) ? $mediaSize : 'l';
    $tag = 'h'.max(2, min(4, (int) $level));
    $id ??= 'feature-'.\Illuminate\Support\Str::slug($title);
    $hasMedia = $variant !== 'links' && filled($image);
    $creditPlacement ??= $variant === 'overlap' ? 'edge' : 'below';
    $ratio ??= $variant === 'overlap' ? '4-3' : '3-2';
    $context = match ($tone) { 'dark' => 'vdbs-on-dark', 'brand' => 'vdbs-on-brand', default => null };
    $bandVariant = in_array($variant, ['side', 'links'], true);
    $classes = [
        'vdbs-feature',
        "vdbs-feature--{$variant}",
        "vdbs-feature--tone-{$tone}",
        "vdbs-feature--media-{$mediaSize}",
        'vdbs-feature--reverse' => $reverse,
        'vdbs-feature--no-media' => ! $hasMedia,
    ];
@endphp

<section {{ $attributes->class($classes)->class([$context ?? '' => $bandVariant && $context]) }} aria-labelledby="{{ $id }}-title" id="{{ $id }}">
    <div class="vdbs-feature__inner">
        <div class="vdbs-feature__text {{ $bandVariant ? '' : $context }}">
            @if ($eyebrow)
                <p class="vdbs-feature__eyebrow">{{ $eyebrow }}</p>
            @endif

            <{{ $tag }} class="vdbs-feature__title" id="{{ $id }}-title">{{ $title }}</{{ $tag }}>

            <div class="vdbs-feature__body">{{ $slot }}</div>

            @if (isset($actions) && $variant !== 'links')
                <div class="vdbs-feature__actions">{{ $actions }}</div>
            @endif
        </div>

        @if ($variant === 'links' && isset($actions))
            <div class="vdbs-feature__actions vdbs-feature__actions--stack">{{ $actions }}</div>
        @endif

        @if ($hasMedia)
            <div class="vdbs-feature__media">
                <x-vdbs.media.figure :src="$image" :alt="$imageAlt" :credit="$credit" :caption="$caption" :ratio="$ratio" :credit-placement="$creditPlacement" />
            </div>
        @endif
    </div>
</section>
