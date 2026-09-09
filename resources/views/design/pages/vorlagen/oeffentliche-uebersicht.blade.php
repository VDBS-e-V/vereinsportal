@extends('design.layout')

@section('title', 'Öffentliche Übersichtsseite')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Öffentlich</p>
            <h1 class="page-title__title">Öffentliche Übersichtsseite</h1>
            <p class="page-title__lead">
                Für thematische Einstiege mit aktuellen Informationen, Terminen,
                Ressourcen und einem klaren Kontaktweg.
            </p>
        </header>

        <div class="design-example stack stack--lg">
            <header class="page-title">
                <p class="page-title__kicker">VDBS e.V.</p>
                <h2 class="page-title__title">Gemeinsam Schule gestalten</h2>
                <p class="page-title__lead">
                    Aktuelle Informationen, Veranstaltungen und hilfreiche Ressourcen.
                </p>
            </header>

            <dl class="key-facts">
                <div><dt>Mitglieder</dt><dd>1.248</dd></div>
                <div><dt>Aktive Gruppen</dt><dd>36</dd></div>
                <div><dt>Termine</dt><dd>18</dd></div>
            </dl>

            <section class="stack">
                <h3>Aktuelles</h3>
                <div class="news-list">
                    <x-vdbs.news-teaser
                        title="Mitgliederversammlung im November"
                        url="#"
                        date="09.09.2026"
                        category="Verein"
                        description="Die wichtigsten Informationen zur kommenden Mitgliederversammlung."
                    />
                </div>
            </section>

            <section class="stack">
                <h3>Nächster Termin</h3>
                <div class="event-list">
                    <x-vdbs.event-teaser
                        day="12"
                        month="Nov"
                        year="2026"
                        title="Mitgliederversammlung 2026"
                        url="#"
                        time="18:30 Uhr"
                        location="Berlin"
                    />
                </div>
            </section>

            <section class="stack">
                <h3>Ressourcen</h3>
                <div class="resource-list">
                    <x-vdbs.resource-item
                        title="Leitfaden für neue Mitglieder"
                        url="#"
                        description="Grundlagen für den Einstieg in die Vereinsarbeit."
                        meta="PDF · 1,4 MB"
                    />
                </div>
            </section>

            <section class="stack">
                <h3>Kontakt</h3>
                <div class="contact-list">
                    <x-vdbs.contact-block
                        name="Geschäftsstelle"
                        role="Allgemeine Anfragen"
                        email="kontakt@example.test"
                        phone="+49 30 123456-0"
                    />
                </div>
            </section>
        </div>
    </div>
@endsection
