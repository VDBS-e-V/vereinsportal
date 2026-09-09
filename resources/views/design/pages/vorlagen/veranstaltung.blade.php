@extends('design.layout')

@section('title', 'Veranstaltungsdetail')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Veranstaltung</p>
            <h1 class="page-title__title">Veranstaltungsdetail</h1>
            <p class="page-title__lead">
                Für Termine mit Zeit, Ort, Anmeldung, Beschreibung,
                Kontakt und veranstaltungsbezogenen Ressourcen.
            </p>
        </header>

        <div class="design-example stack stack--lg">
            <header class="page-title page-title--split">
                <div class="stack stack--sm">
                    <p class="page-title__kicker">Veranstaltung</p>
                    <h2 class="page-title__title">Mitgliederversammlung 2026</h2>
                    <p class="page-title__lead">
                        Ordentliche Mitgliederversammlung mit Berichten und Wahlen.
                    </p>
                </div>
                <div class="page-title__actions">
                    <button class="btn" type="button">Anmelden</button>
                </div>
            </header>

            <div class="event-detail-meta">
                <div class="event-detail-meta__item">
                    <span class="event-detail-meta__label">Datum</span>
                    <span class="event-detail-meta__value">12.11.2026</span>
                </div>
                <div class="event-detail-meta__item">
                    <span class="event-detail-meta__label">Zeit</span>
                    <span class="event-detail-meta__value">18:30 Uhr</span>
                </div>
                <div class="event-detail-meta__item">
                    <span class="event-detail-meta__label">Ort</span>
                    <span class="event-detail-meta__value">Berlin</span>
                </div>
                <div class="event-detail-meta__item">
                    <span class="event-detail-meta__label">Anmeldung</span>
                    <span class="event-detail-meta__value">bis 05.11.2026</span>
                </div>
            </div>

            <div class="article__body">
                <h3>Über die Veranstaltung</h3>
                <p>
                    Hier stehen Programm, organisatorische Hinweise und weitere
                    Informationen für Teilnehmende.
                </p>
            </div>

            <section class="stack">
                <h3>Ansprechperson</h3>
                <div class="contact-list">
                    <x-vdbs.contact-block
                        name="Erika Muster"
                        role="Organisation"
                        email="veranstaltungen@example.test"
                        phone="+49 30 123456-30"
                    />
                </div>
            </section>

            <section class="stack">
                <h3>Dokumente</h3>
                <div class="resource-list">
                    <x-vdbs.resource-item
                        title="Tagesordnung"
                        url="#"
                        description="Tagesordnung der Mitgliederversammlung."
                        meta="PDF · 420 KB"
                    />
                </div>
            </section>
        </div>
    </div>
@endsection
