@props(['title', 'eyebrow' => null, 'lead' => null, 'breadcrumb' => [], 'level' => 1, 'id' => null, 'inline' => false])

{{--
    Seitenkopf (Paket "layout"): Brotkrumen, Titel, Einleitung, Statusangaben und Aktionen; darunter optional Tabs.
    breadcrumb  Liste aus ['label' => string, 'href' => ?string] (wie <x-vdbs.navigation.breadcrumb>)
    inline      true: Brotkrumen im Seitenkopf statt in der Leiste unter dem Kopfbereich (nur für Muster/Beispiele)
    level       Überschriftenebene 1–3 (pro Seite genau eine h1)
    Slots: meta (z. B. Badges neben dem Titel), actions (Buttons rechts), tabs (z. B. <x-vdbs.navigation.tab-nav>)
--}}

@php
    // Erste Brotkrume = Startseite des Bereichs (Seiten ohne Bereich bleiben unverändert)
    $breadcrumb = \App\Support\PortalNavigation::breadcrumb($breadcrumb, request()->route()?->getName());
    $tag = 'h'.max(1, min(3, (int) $level));
    $id ??= 'page-'.\Illuminate\Support\Str::slug($title);
@endphp

{{-- Die Brotkrumen gehören zum Kopfbereich: Sie stehen als farbige Leiste direkt darunter (die Layouts geben den Stack nach dem Kopfbereich aus). --}}
@if (count($breadcrumb) > 0 && ! $inline)
    @push('vdbs-breadcrumb')
        <x-vdbs.navigation.breadcrumb :items="$breadcrumb" />
    @endpush
@endif

<header {{ $attributes->class('vdbs-page-header') }} aria-labelledby="{{ $id }}-title">
    @if (count($breadcrumb) > 0 && $inline)
        <x-vdbs.navigation.breadcrumb :items="$breadcrumb" />
    @endif

    <div class="vdbs-page-header__main">
        <div class="vdbs-page-header__text">
            @if ($eyebrow) <p class="vdbs-page-header__eyebrow">{{ $eyebrow }}</p> @endif
            <div class="vdbs-page-header__title-row">
                <{{ $tag }} class="vdbs-page-header__title" id="{{ $id }}-title">{{ $title }}</{{ $tag }}>
                @isset($meta) <div class="vdbs-page-header__meta">{{ $meta }}</div> @endisset
            </div>
            @if ($lead) <p class="vdbs-page-header__lead">{{ $lead }}</p> @endif
        </div>

        @isset($actions)
            <div class="vdbs-page-header__actions">{{ $actions }}</div>
        @endisset
    </div>

    @isset($tabs)
        <div class="vdbs-page-header__tabs">{{ $tabs }}</div>
    @endisset
</header>
