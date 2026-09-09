@extends('design.layout')

@section('title', 'Medien & Abbildungen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Medien &amp; Abbildungen</h1>
            <p class="page-title__lead">
                Bilder erhalten feste, wiederkehrende Seitenverhältnisse.
                Bildunterschriften und Quellen gehören semantisch zum jeweiligen <code>figure</code>.
            </p>
        </header>

        <section class="stack">
            <h2>3:2 für Teaser und redaktionelle Einstiege</h2>

            <figure class="media-figure">
                <div class="media-frame media-frame--3-2 media-frame--contain">
                    <img
                        src="{{ asset('images/brand/vdbs-logo.png') }}"
                        alt="VDBS Logo als Beispielgrafik"
                    >
                </div>
                <figcaption class="media-figure__caption">
                    <p>Beispiel einer Abbildung im Verhältnis 3:2.</p>
                    <p class="media-figure__source">Quelle: VDBS e.V.</p>
                </figcaption>
            </figure>
        </section>

        <section class="section stack">
            <h2>Weitere Standardformate</h2>

            <div class="media-grid">
                <figure class="media-figure">
                    <div class="media-frame media-frame--16-9 media-frame--contain">
                        <img
                            src="{{ asset('images/brand/vdbs-logo.png') }}"
                            alt="VDBS Logo in einer breiten Medienfläche"
                        >
                    </div>
                    <figcaption class="media-figure__caption">
                        <p>16:9 für breite Artikel- oder Hero-Medien.</p>
                    </figcaption>
                </figure>

                <figure class="media-figure">
                    <div class="media-frame media-frame--1-1 media-frame--contain">
                        <img
                            src="{{ asset('images/brand/vdbs-logo.png') }}"
                            alt="VDBS Logo in einer quadratischen Medienfläche"
                        >
                    </div>
                    <figcaption class="media-figure__caption">
                        <p>1:1 für kompakte Personen-, Logo- oder Übersichtsmedien.</p>
                    </figcaption>
                </figure>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li><strong>3:2</strong> ist Standard für Teaserbilder.</li>
                <li><strong>16:9</strong> wird für breite Artikel- und Hero-Medien verwendet.</li>
                <li><strong>1:1</strong> eignet sich für kompakte Personen-, Logo- und Übersichtsmedien.</li>
                <li><code>cover</code> ist Standard für Fotos; <code>contain</code> für Logos und wichtige Grafiken.</li>
                <li>Poster, Infografiken und Dokumentabbildungen dürfen ihr Originalformat behalten.</li>
            </ul>
        </section>
    </div>
@endsection
