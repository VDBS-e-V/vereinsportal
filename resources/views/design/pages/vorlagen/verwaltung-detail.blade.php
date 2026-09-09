@extends('design.layout')

@section('title', 'Verwaltungs-Detail')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Verwaltung</p>
            <h1 class="page-title__title">Verwaltungs-Detail</h1>
            <p class="page-title__lead">
                Für einen einzelnen Datensatz mit klaren Aktionen, lokalen Ansichten
                und strukturierten Detailinformationen.
            </p>
        </header>

        <div class="design-example stack stack--lg">
            <header class="page-title page-title--split">
                <div class="stack stack--sm">
                    <p class="page-title__kicker">Mitgliederverwaltung</p>
                    <h2 class="page-title__title">Erika Muster</h2>
                    <p class="page-title__lead">Mitgliedsnummer VDBS-10428</p>
                </div>
                <div class="page-title__actions">
                    <button class="btn btn--secondary" type="button">Bearbeiten</button>
                </div>
            </header>

            <nav class="page-tabs" aria-label="Mitgliedsansicht">
                <ul class="page-tabs__list">
                    <li><a class="page-tabs__link" href="#" aria-current="page">Stammdaten</a></li>
                    <li><a class="page-tabs__link" href="#">Mitgliedschaft</a></li>
                    <li><a class="page-tabs__link" href="#">Historie</a></li>
                </ul>
            </nav>

            <dl class="key-facts">
                <div><dt>Status</dt><dd>Aktiv</dd></div>
                <div><dt>Eintritt</dt><dd>12.03.2021</dd></div>
                <div><dt>Rolle</dt><dd>Mitglied</dd></div>
            </dl>

            <dl class="metadata-list">
                <div><dt>E-Mail</dt><dd>erika.muster@example.test</dd></div>
                <div><dt>Telefon</dt><dd>+49 30 123456-10</dd></div>
                <div><dt>Zuletzt geändert</dt><dd>09.09.2026, 14:30 Uhr</dd></div>
            </dl>

            <section class="stack">
                <h3>Dateien</h3>
                <div class="file-list">
                    <x-vdbs.file-item
                        name="mitgliedsantrag.pdf"
                        kind="PDF"
                        meta="820 KB · 12.03.2021"
                    >
                        <x-slot:status>
                            <x-vdbs.status type="success">Verfügbar</x-vdbs.status>
                        </x-slot:status>
                        <x-slot:actions>
                            <a class="btn btn--secondary btn--sm" href="#">Öffnen</a>
                        </x-slot:actions>
                    </x-vdbs.file-item>
                </div>
            </section>

            <x-vdbs.danger-zone
                title="Mitgliedschaft beenden"
                description="Diese Aktion beendet die aktive Mitgliedschaft."
            >
                <x-slot:actions>
                    <button class="btn btn--danger" type="button">Mitgliedschaft beenden</button>
                </x-slot:actions>
            </x-vdbs.danger-zone>
        </div>
    </div>
@endsection
