@extends('design.layout')

@section('title', 'Veranstaltungen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Veranstaltungen</h1>
            <p class="page-title__lead">
                Termine zeigen Datum, Uhrzeit und Ort schnell erfassbar,
                bleiben aber vollständig als Text zugänglich.
            </p>
        </header>

        <section class="stack">
            <h2>Terminliste</h2>

            <div class="event-list">
                <x-vdbs.event-teaser
                    day="12"
                    month="Nov"
                    year="2026"
                    title="Mitgliederversammlung 2026"
                    url="#"
                    time="18:30 Uhr"
                    location="Berlin"
                    description="Ordentliche Mitgliederversammlung mit Berichten und Wahlen."
                />
                <x-vdbs.event-teaser
                    day="03"
                    month="Dez"
                    year="2026"
                    title="Online-Austausch für Teamende"
                    url="#"
                    time="16:00 Uhr"
                    location="Online"
                    description="Austausch zu aktuellen Themen aus der Vereinsarbeit."
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Detail-Metadaten</h2>

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
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Deutsche Datums- und Zeitdarstellung wird konsistent verwendet.</li>
                <li>Das dekorative Datumsfeld ersetzt nie den zugänglichen Termintext.</li>
                <li>Online-Termine nennen „Online“ als Ort statt einen leeren Ortswert.</li>
                <li>Anmelde- und Statusinformationen werden bei Bedarf als eigene Metadaten ergänzt.</li>
            </ul>
        </section>
    </div>
@endsection
