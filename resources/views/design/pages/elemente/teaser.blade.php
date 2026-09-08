@extends('design.layout')

@section('title', 'Teaser')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Teaser</h1>
            <p class="page-title__lead">
                Lineare Teaser sind der bevorzugte Einstieg in Themen, Bereiche
                und redaktionelle Inhalte. Karten bleiben die Ausnahme.
            </p>
        </header>

        <section class="stack">
            <h2>Standard</h2>
            <p>
                Für Informationsübersichten sind lineare Teaser der Standard.
                Karten werden nur verwendet, wenn ein Inhalt tatsächlich eine
                eigenständige Einheit bildet.
            </p>

            <div class="teaser-list">
                <article class="teaser">
                    <span class="badge">Bereich</span>
                    <h3><a href="#">Mitgliedsdaten verwalten</a></h3>
                    <p>Kurze fachliche Beschreibung des Bereichs.</p>
                </article>

                <article class="teaser">
                    <span class="badge">Bereich</span>
                    <h3><a href="#">Kommunikation</a></h3>
                    <p>Vorlagen, Zustellungen und weitere Kommunikationsfunktionen.</p>
                </article>
            </div>
        </section>
    </div>
@endsection
