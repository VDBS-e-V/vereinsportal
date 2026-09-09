@extends('design.layout')

@section('title', 'Elemente')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Web Components</p>
            <h1 class="page-title__title">Elemente</h1>
            <p class="page-title__lead">
                Wiederverwendbare UI-Muster sind nach Aufgabenbereichen getrennt.
                Jede Unterseite dokumentiert Varianten, Zustände, Einsatzregeln
                und Accessibility-Anforderungen der jeweiligen Komponentenfamilie.
            </p>
        </header>

        <section class="stack">
            <h2>Aktionen</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.buttons') }}">Buttons</a>
                    </h3>
                    <p>Primäre, sekundäre, ruhige, destruktive und zustandsabhängige Aktionen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Eingaben</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.formulare') }}">Formulare</a>
                    </h3>
                    <p>Felder, Auswahlmuster, Validierung sowie Disabled- und Readonly-Zustände.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Feedback</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.hinweise') }}">Hinweise</a>
                    </h3>
                    <p>Information, Erfolg, Warnung und Fehler als semantische Rückmeldung.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.empty-states') }}">Leere Zustände</a>
                    </h3>
                    <p>Fehlende Daten oder Suchergebnisse werden erklärt und mit einer sinnvollen Aktion verbunden.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.loading') }}">Loading &amp; Busy</a>
                    </h3>
                    <p>Laufende Aktionen, Bereichsladezustände und bestimmter Fortschritt.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.validierung') }}">Validierungsübersicht</a>
                    </h3>
                    <p>Mehrere Formularfehler werden zusammengefasst und mit den betroffenen Feldern verknüpft.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.bestaetigung') }}">Bestätigung &amp; Gefahr</a>
                    </h3>
                    <p>Kontext, Bestätigung und eindeutige Darstellung destruktiver Aktionen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Navigation</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.navigation') }}">Lokale Navigation &amp; Pagination</a>
                    </h3>
                    <p>Bereichsnavigation, seitenbasierte Tabs und Seitennavigation für lokale Inhalte.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Inhalte</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.teaser') }}">Teaser</a>
                    </h3>
                    <p>Lineare Einstiege und eigenständige Inhaltsmodule für Übersichtsseiten.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.metadaten') }}">Status &amp; Metadaten</a>
                    </h3>
                    <p>Badges, fachliche Zustände und strukturierte Schlüssel-Wert-Informationen.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.disclosure') }}">Aufklappbare Inhalte</a>
                    </h3>
                    <p>Native Details für ergänzende Informationen, die nicht dauerhaft sichtbar sein müssen.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.medien') }}">Medien &amp; Abbildungen</a>
                    </h3>
                    <p>Bildformate, Seitenverhältnisse, Objektanpassung, Bildunterschriften und Quellen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Overlays</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.dialoge') }}">Dialoge &amp; Overlays</a>
                    </h3>
                    <p>Native modale Dialoge für fokussierte Entscheidungen und Bestätigungen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Daten</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.tabellen') }}">Tabellen</a>
                    </h3>
                    <p>Tabellarische Daten, kompakte Varianten und responsive Grundregeln.</p>
                </article>

                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.suche-filter') }}">Suche &amp; Filter</a>
                    </h3>
                    <p>Lokale Suche, optionale Filter und Toolbar-Muster für Verwaltungslisten.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Dateien</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3>
                        <a href="{{ route('design.elemente.dateien') }}">Dateien &amp; Uploads</a>
                    </h3>
                    <p>Dateiauswahl, Uploadhinweise, Dateistatus und Download-Aktionen.</p>
                </article>
            </div>
        </section>
    </div>
@endsection
