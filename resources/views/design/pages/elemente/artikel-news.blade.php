@extends('design.layout')

@section('title', 'Artikel & News')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Artikel &amp; News</h1>
            <p class="page-title__lead">
                Redaktionelle Inhalte erhalten eine klare Lesehierarchie aus Titel,
                Einleitung, Metadaten und einem begrenzten Textmaß.
            </p>
        </header>

        <section class="stack">
            <h2>Artikeldetail</h2>

            <article class="article">
                <header class="article__header">
                    <p class="article__kicker">Vereinsleben</p>
                    <h3 class="article__title">Neue Impulse für die gemeinsame Arbeit</h3>
                    <p class="article__lead">
                        Ein Beispiel für einen redaktionellen Einstieg mit klarer
                        Hierarchie und gut lesbarer Textbreite.
                    </p>
                    <ul class="article__meta">
                        <li>09.09.2026</li>
                        <li>Redaktion VDBS</li>
                        <li>5 Minuten Lesezeit</li>
                    </ul>
                </header>

                <div class="article__body">
                    <p>
                        Fließtext bleibt auf eine angenehme Lesebreite begrenzt.
                        Zwischenüberschriften strukturieren längere Inhalte.
                    </p>

                    <p class="editorial-accent">
                        Gute redaktionelle Gestaltung entsteht durch Hierarchie,
                        nicht durch dekorative Effekte.
                    </p>

                    <h4>Ein weiterer Abschnitt</h4>
                    <p>
                        Medien, Ressourcen und verwandte Inhalte können nach dem
                        eigentlichen Artikel mit ihren jeweiligen Komponenten ergänzt werden.
                    </p>
                </div>
            </article>
        </section>

        <section class="section stack">
            <h2>News-Liste</h2>

            <div class="news-list">
                <x-vdbs.news-teaser
                    title="Mitgliederversammlung im November"
                    url="#"
                    date="09.09.2026"
                    category="Verein"
                    description="Die wichtigsten Informationen zur kommenden Mitgliederversammlung."
                />
                <x-vdbs.news-teaser
                    title="Neue Materialien im Portal"
                    url="#"
                    date="04.09.2026"
                    category="Portal"
                    description="Weitere Arbeitsmaterialien stehen für Mitglieder bereit."
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Artikeltext nutzt die begrenzte Textbreite des Designsystems.</li>
                <li>Datum, Autorenschaft und weitere Metadaten bleiben sichtbarer Text.</li>
                <li>Die Akzentschrift wird nur für kurze redaktionelle Aussagen eingesetzt.</li>
                <li>News-Listen bleiben linear und werden nicht automatisch zu Kartenrastern.</li>
            </ul>
        </section>
    </div>
@endsection
