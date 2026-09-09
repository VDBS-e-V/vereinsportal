@extends('design.layout')

@section('title', 'Muster')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Designsystem</p>
            <h1 class="page-title__title">Muster</h1>
            <p class="page-title__lead">
                Muster kombinieren mehrere Elemente zu wiederkehrenden fachlichen
                und redaktionellen Darstellungen. Die bestehenden URLs bleiben
                erhalten; im Designbereich werden sie hier gemeinsam organisiert.
            </p>
        </header>

        <section class="stack">
            <h2>Redaktion &amp; Medien</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.teaser') }}">Teaser</a></h3>
                    <p>Lineare Einstiege und eigenständige Inhaltsmodule für Übersichtsseiten.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.medien') }}">Medien &amp; Abbildungen</a></h3>
                    <p>Bildformate, Seitenverhältnisse, Objektanpassung, Bildunterschriften und Quellen.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.artikel-news') }}">Artikel &amp; News</a></h3>
                    <p>Redaktionelle Detailseiten, Metadaten, Lesehierarchie und News-Einstiege.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Termine &amp; Kontakt</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.veranstaltungen') }}">Veranstaltungen</a></h3>
                    <p>Terminlisten und Veranstaltungsmetadaten mit Datum, Zeit und Ort.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.kontakte') }}">Kontakte</a></h3>
                    <p>Ansprechpersonen, Zuständigkeiten und direkt nutzbare Kontaktwege.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Ressourcen &amp; Datensätze</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.ressourcen') }}">Ressourcen &amp; Linklisten</a></h3>
                    <p>Weiterführende Inhalte, Downloads und verwandte Ziele als klare Listen.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.key-facts') }}">Key Facts</a></h3>
                    <p>Wenige wichtige Kennzahlen und Fakten ohne Dashboard-Optik.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente.datensatzlisten') }}">Datensatzlisten</a></h3>
                    <p>Lineare Datensätze mit wenigen Metadaten als Alternative zur Tabelle.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Abgrenzung</h2>
            <p>
                Kleine, wiederverwendbare UI-Bausteine wie Buttons, Formfelder,
                Status, Dialoge und Tabellen bleiben unter
                <a href="{{ route('design.elemente') }}">Elemente</a>.
            </p>
        </section>
    </div>
@endsection
