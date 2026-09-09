@extends('design.layout')

@section('title', 'Übersicht')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Arbeitsbereich</p>
            <h1 class="page-title__title">Vorlagen & Elemente</h1>
            <p class="page-title__lead">
                Verbindliche Grundlagen und wiederverwendbare Bausteine für das
                VDBS-Vereinsportal. Die Referenz nutzt denselben Seitenkopf und
                dieselbe Inhaltsstruktur wie die eigentliche Anwendung.
            </p>
        </header>

        <section class="stack">
            <h2>Designbereiche</h2>

            <div class="teaser-list">
                <article class="teaser">
                    <h3><a href="{{ route('design.grundlagen') }}">Grundlagen</a></h3>
                    <p>Farben, Typografie, Abstände, Flächen, Linien und Schatten.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.layout') }}">Layout</a></h3>
                    <p>Frames, Inhaltsbreiten, Seitenraster und vertikale Grundstruktur.</p>
                </article>

                @if (\Illuminate\Support\Facades\Route::has('design.header'))
                    <article class="teaser">
                        <h3><a href="{{ route('design.header') }}">Header</a></h3>
                        <p>Bereichsnavigation, Seitennavigation, Benutzerkonto und Mobile-Menü.</p>
                    </article>
                @endif

                <article class="teaser">
                    <h3><a href="{{ route('design.elemente') }}">Elemente</a></h3>
                    <p>Buttons, Formulare, Statusmeldungen, Tabellen und technische UI-Bausteine.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.muster') }}">Muster</a></h3>
                    <p>Redaktionelle und fachliche Kombinationen wie Artikel, Termine und Kontakte.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.vorlagen') }}">Vorlagen</a></h3>
                    <p>Vollständige Seitenstrukturen für öffentliche und interne Anwendungsfälle.</p>
                </article>

                <article class="teaser">
                    <h3><a href="{{ route('design.print') }}">Print</a></h3>
                    <p>Druckregeln für Inhalte, Tabellen, Details, Metadaten und Aktionen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Neue Designseite</h2>
            <p class="vdbs-text-container">
                Der Generator erzeugt View und Route. Die neue Seite wird automatisch
                in die horizontale Navigation des Designbereichs aufgenommen.
            </p>

            <pre class="design-code">php artisan make:design-page buttons --title="Buttons"
php artisan make:design-page formulare/textfelder --title="Textfelder"</pre>
        </section>
    </div>
@endsection
