@extends('design.layout')

@section('title', 'Key Facts')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Key Facts</h1>
            <p class="page-title__lead">
                Wenige zentrale Kennzahlen oder Fakten können kompakt hervorgehoben werden,
                ohne daraus ein Dashboard mit dekorativen Statistik-Karten zu machen.
            </p>
        </header>

        <section class="stack">
            <h2>Faktenübersicht</h2>

            <dl class="key-facts">
                <div>
                    <dt>Mitglieder</dt>
                    <dd>1.248</dd>
                </div>
                <div>
                    <dt>Aktive Gruppen</dt>
                    <dd>36</dd>
                </div>
                <div>
                    <dt>Veranstaltungen 2026</dt>
                    <dd>18</dd>
                </div>
                <div>
                    <dt>Stand</dt>
                    <dd>09.09.2026</dd>
                </div>
            </dl>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Key Facts enthalten nur tatsächlich wichtige Werte.</li>
                <li>Bezeichnung und Wert bilden eine echte Definitionsbeziehung.</li>
                <li>Keine Trendpfeile oder Farben ohne fachlich definierte Bedeutung.</li>
                <li>Für umfangreiche Analysen werden Tabellen oder spätere Datenvisualisierungen verwendet.</li>
            </ul>
        </section>
    </div>
@endsection
