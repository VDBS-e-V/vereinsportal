@extends('design.layout')

@section('title', 'Vorlagen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Seitenstruktur</p>
            <h1 class="page-title__title">Vorlagen</h1>
            <p class="page-title__lead">
                Vorlagen kombinieren Elemente und Muster zu vollständigen Seitentypen.
                Sie dienen als Ausgangspunkt für neue Fachseiten und nicht als starre
                Kopiervorlage für Fachlogik.
            </p>
        </header>

        <section class="stack">
            <h2>Verwaltung</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.verwaltung-liste') }}">Verwaltungs-Liste</a></h3>
                    <p>Suche, Filter, Toolbar, Tabelle und Pagination für größere Datensätze.</p>
                </article>
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.verwaltung-detail') }}">Verwaltungs-Detail</a></h3>
                    <p>Datensatzkopf, Tabs, Metadaten, Dateien und destruktive Aktionen.</p>
                </article>
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.formular') }}">Create/Edit-Formular</a></h3>
                    <p>Formularstruktur mit Validierungsübersicht, Feldern und Aktionsbereich.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Öffentlich &amp; redaktionell</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.oeffentliche-uebersicht') }}">Öffentliche Übersichtsseite</a></h3>
                    <p>Fakten, aktuelle Meldungen, Termine, Ressourcen und Kontakt.</p>
                </article>
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.artikel') }}">Artikelseite</a></h3>
                    <p>Redaktioneller Inhalt mit Medien und weiterführenden Ressourcen.</p>
                </article>
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.veranstaltung') }}">Veranstaltungsdetail</a></h3>
                    <p>Terminmetadaten, Beschreibung, Anmeldung, Kontakt und Dokumente.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Systemseiten</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen.fehlerseiten') }}">Fehlerseiten</a></h3>
                    <p>403, 404 und 500 mit klarer Orientierung und sinnvollen nächsten Schritten.</p>
                </article>
            </div>
        </section>
    </div>
@endsection
