@extends('design.layout')

@section('title', 'Ressourcen & Linklisten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Ressourcen &amp; Linklisten</h1>
            <p class="page-title__lead">
                Verwandte Inhalte, Downloads und weiterführende Ressourcen werden
                als klare Listen statt als Sammlung beliebiger Karten dargestellt.
            </p>
        </header>

        <section class="stack">
            <h2>Ressourcenliste</h2>

            <div class="resource-list">
                <x-vdbs.resource-item
                    title="Leitfaden für neue Mitglieder"
                    url="#"
                    description="Grundlagen und Hinweise für den Einstieg in die Vereinsarbeit."
                    meta="PDF · 1,4 MB"
                >
                    <x-slot:action>
                        <a class="btn btn--secondary btn--sm" href="#">Öffnen</a>
                    </x-slot:action>
                </x-vdbs.resource-item>

                <x-vdbs.resource-item
                    title="Materialsammlung für Teamende"
                    url="#"
                    description="Weiterführende Materialien und Vorlagen im Mitgliederbereich."
                    meta="Interne Seite"
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Verwandte Links</h2>

            <ul class="related-links">
                <li><a href="#">Über das Vereinsportal</a></li>
                <li><a href="#">Häufige Fragen</a></li>
                <li><a href="#">Kontakt und Unterstützung</a></li>
            </ul>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Linktitel beschreibt das Ziel statt „Mehr erfahren“ zu wiederholen.</li>
                <li>Dateityp und Dateigröße werden bei Downloads als Metadaten angegeben.</li>
                <li>Externe Ziele werden bei Bedarf textlich oder mit dem zentralen Icon-System gekennzeichnet.</li>
                <li>Verwandte Links bleiben eine kompakte lineare Liste.</li>
            </ul>
        </section>
    </div>
@endsection
