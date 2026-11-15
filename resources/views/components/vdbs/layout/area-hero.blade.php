@props(['area', 'title', 'lead' => null, 'greeting' => null])

{{--
    Kopfbereich der Startseite eines Bereichs (Paket "layout"): Fläche mit Bereichsfarbe als Balken, Icon, Titel, Begrüßung und Einleitung.
    area      App\Support\PortalAreas (liefert Icon und Gruppenfarbe)
    greeting  z. B. "Guten Tag, Erika" (freiwillig)
    Slots:    Standard (zusätzliche Inhalte unter der Einleitung, z. B. Hinweise), actions (Buttons), aside (Karte rechts, z. B. der eigene Stand)
    Die Seite verwendet width="full"; der Inhalt darunter steht in <x-vdbs.layout.container>.
--}}

@php
    $mark = $area->groupMark();
    $headingId = 'area-hero-'.$area->value;
@endphp

<section {{ $attributes->class(['vdbs-area-hero']) }} @if ($mark) style="--area-tone: var(--group-{{ $mark }})" @endif aria-labelledby="{{ $headingId }}">
    <x-vdbs.layout.container>
        <div class="vdbs-area-hero__inner">
            <div class="vdbs-area-hero__text">
                <p class="vdbs-area-hero__eyebrow"><span class="vdbs-area-hero__icon" aria-hidden="true"><x-vdbs.icon :name="$area->icon()" :size="22" /></span>Bereich {{ $area->label() }}</p>
                <h1 class="vdbs-area-hero__title" id="{{ $headingId }}">{{ $title }}</h1>
                @if ($greeting)<p class="vdbs-area-hero__greeting">{{ $greeting }}</p>@endif
                @if ($lead)<p class="vdbs-area-hero__lead">{{ $lead }}</p>@endif
                {{ $slot }}
                @isset($actions)
                    <div class="vdbs-area-hero__actions">{{ $actions }}</div>
                @endisset
            </div>
            @isset($aside)
                <aside class="vdbs-area-hero__aside">{{ $aside }}</aside>
            @endisset
        </div>
    </x-vdbs.layout.container>
</section>
