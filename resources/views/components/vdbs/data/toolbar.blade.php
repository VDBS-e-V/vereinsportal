@props([
    'label' => 'Liste filtern',
    'action' => null,
    'searchName' => 'q',
    'searchLabel' => 'Suche',
    'searchPlaceholder' => 'Suchen …',
    'submit' => 'Anwenden',
    'count' => null,
    'active' => [],
    'resetHref' => null,
])

{{--
    Werkzeugleiste über Tabellen und Listen (Paket "data"): Suche, Filter, aktive Filter als entfernbare Tags und
    Trefferzahl. Ein GET-Formular, die Filter stehen also in der Adresse (teilbar, mit Zurück-Knopf nutzbar).

    label              Beschriftung der Suche für Screenreader (role="search")
    search-name        Name des Suchfelds (Standard q); search-label, search-placeholder
    count              Text zur Trefferzahl, z. B. "42 Nutzer" (wird bei Änderung höflich vorgelesen)
    active             Liste aus ['label' => 'Rolle: Vorstand', 'href' => 'Adresse ohne diesen Filter']
    reset-href         Adresse ohne alle Filter ("Zurücksetzen")
    Slots: Standard = Filterfelder (z. B. <x-vdbs.forms.select size="m">), actions = Buttons rechts (z. B. "Nutzer anlegen")
--}}

@php
    $searchId = 'toolbar-'.\App\Support\Forms\FieldState::idFor($searchName);
    $current = request()->query($searchName);
@endphp

<form {{ $attributes->class('vdbs-toolbar') }} method="get" role="search" aria-label="{{ $label }}" @if ($action) action="{{ $action }}" @endif>
    <div class="vdbs-toolbar__row">
        <div class="vdbs-toolbar__search">
            <label class="vdbs-sr-only" for="{{ $searchId }}">{{ $searchLabel }}</label>
            <div class="vdbs-input-group vdbs-toolbar__group">
                <span class="vdbs-input-group__addon"><x-vdbs.icon name="search" :size="18" /></span>
                <input id="{{ $searchId }}" name="{{ $searchName }}" type="search" class="vdbs-control vdbs-control--bare" placeholder="{{ $searchPlaceholder }}" autocomplete="off" @if (is_string($current) && $current !== '') value="{{ $current }}" @endif>
            </div>
        </div>

        @if (! $slot->isEmpty())
            <div class="vdbs-toolbar__filters">{{ $slot }}</div>
        @endif

        <div class="vdbs-toolbar__buttons">
            <x-vdbs.actions.button type="submit" variant="secondary" icon="filter">{{ $submit }}</x-vdbs.actions.button>
            @if ($resetHref && count($active) > 0)
                <x-vdbs.actions.button :href="$resetHref" variant="tertiary">Zurücksetzen</x-vdbs.actions.button>
            @endif
        </div>

        @isset($actions)
            <div class="vdbs-toolbar__actions">{{ $actions }}</div>
        @endisset
    </div>

    @if ($count !== null || count($active) > 0)
        <div class="vdbs-toolbar__meta">
            @if ($count !== null)
                <p class="vdbs-toolbar__count" role="status">{{ $count }}</p>
            @endif
            @if (count($active) > 0)
                <ul class="vdbs-toolbar__tags" aria-label="Aktive Filter">
                    @foreach ($active as $tag)
                        <x-vdbs.data.filter-tag :label="$tag['label']" :href="$tag['href']" />
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</form>
