@extends('design.layout')

@section('title', 'Artikelseite')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Redaktion</p>
            <h1 class="page-title__title">Artikelseite</h1>
            <p class="page-title__lead">
                Für längere redaktionelle Inhalte mit klarer Lesehierarchie,
                optionalem Medium und weiterführenden Ressourcen.
            </p>
        </header>

        <div class="design-example stack stack--lg">
            <article class="article">
                <header class="article__header">
                    <p class="article__kicker">Vereinsleben</p>
                    <h2 class="article__title">Neue Impulse für die gemeinsame Arbeit</h2>
                    <p class="article__lead">
                        Eine kurze Einleitung führt in das Thema ein und fasst den Kern zusammen.
                    </p>
                    <ul class="article__meta">
                        <li>09.09.2026</li>
                        <li>Redaktion VDBS</li>
                        <li>5 Minuten Lesezeit</li>
                    </ul>
                </header>

                <figure class="media-figure">
                    <div class="media-frame media-frame--16-9 media-frame--contain">
                        <img
                            src="{{ asset('images/brand/vdbs-logo.png') }}"
                            alt="VDBS Logo als Beispielgrafik"
                        >
                    </div>
                    <figcaption class="media-figure__caption">
                        <p>Beispielabbildung innerhalb eines Artikels.</p>
                        <p class="media-figure__source">Quelle: VDBS e.V.</p>
                    </figcaption>
                </figure>

                <div class="article__body">
                    <p>
                        Der Fließtext bleibt auf eine angenehme Lesebreite begrenzt.
                        Zwischenüberschriften strukturieren längere Beiträge.
                    </p>
                    <h3>Weiterführender Abschnitt</h3>
                    <p>
                        Links und Downloads folgen nach dem Inhalt in klar getrennten Listen.
                    </p>
                </div>
            </article>

            <section class="stack">
                <h3>Weiterführende Ressourcen</h3>
                <div class="resource-list">
                    <x-vdbs.resource-item
                        title="Hintergrundmaterial"
                        url="#"
                        description="Ergänzende Informationen zum Artikel."
                        meta="Interne Seite"
                    />
                </div>
            </section>
        </div>
    </div>
@endsection
