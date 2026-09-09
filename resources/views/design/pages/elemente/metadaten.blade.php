@extends('design.layout')

@section('title', 'Status & Metadaten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Status &amp; Metadaten</h1>
            <p class="page-title__lead">
                Badges ordnen Inhalte ein, Status kennzeichnen fachliche Zustände
                und Metadaten stellen kompakte Schlüssel-Wert-Informationen dar.
            </p>
        </header>

        <section class="stack">
            <h2>Badges</h2>
            <p>
                Badges sind kurze Kategorien oder Kennzeichnungen. Sie beschreiben
                keine Erfolg-, Warn- oder Fehlerzustände.
            </p>

            <div class="design-example cluster">
                <x-vdbs.badge>Bereich</x-vdbs.badge>
                <x-vdbs.badge tone="accent">Redaktion</x-vdbs.badge>
            </div>
        </section>

        <section class="section stack">
            <h2>Status</h2>
            <p>
                Ein Status benennt den fachlichen Zustand immer als Text.
                Farbe unterstützt die Bedeutung, ist aber nie der einzige Informationsträger.
            </p>

            <div class="design-example cluster">
                <x-vdbs.status>Unbekannt</x-vdbs.status>
                <x-vdbs.status type="info">In Prüfung</x-vdbs.status>
                <x-vdbs.status type="success">Aktiv</x-vdbs.status>
                <x-vdbs.status type="warning">Ausstehend</x-vdbs.status>
                <x-vdbs.status type="danger">Gesperrt</x-vdbs.status>
            </div>
        </section>

        <section class="section stack">
            <h2>Metadaten</h2>
            <p>
                Schlüssel-Wert-Daten bleiben semantische Definitionslisten.
                Für kompakte Verwaltungsansichten steht eine reduzierte Variante zur Verfügung.
            </p>

            <div class="design-example container--narrow">
                <dl class="metadata-list">
                    <div>
                        <dt>Mitgliedsnummer</dt>
                        <dd>VDBS-10428</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><x-vdbs.status type="success">Aktiv</x-vdbs.status></dd>
                    </div>
                    <div>
                        <dt>Zuletzt geändert</dt>
                        <dd>09.09.2026, 14:30 Uhr</dd>
                    </div>
                    <div>
                        <dt>Zuständiger Bereich</dt>
                        <dd>Mitgliederverwaltung</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Badge = Kategorie oder kurze Einordnung.</li>
                <li>Status = fachlicher Zustand mit verständlichem Text.</li>
                <li>Metadaten = echte Schlüssel-Wert-Beziehungen als <code>dl</code>.</li>
                <li>Keine Farbe ohne textliche Bedeutung.</li>
                <li>Keine beliebigen Pillen oder dekorativen Labels.</li>
            </ul>
        </section>
    </div>
@endsection
