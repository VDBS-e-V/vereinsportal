@extends('design.layout')

@section('title', 'Loading & Busy')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Feedback</p>
            <h1 class="page-title__title">Loading &amp; Busy</h1>
            <p class="page-title__lead">
                Ladezustände zeigen, dass ein Vorgang läuft. Bestehende Inhalte
                bleiben möglichst stabil, damit keine unnötigen Layoutsprünge entstehen.
            </p>
        </header>

        <section class="stack">
            <h2>Inline und als Bereich</h2>

            <div class="design-example stack">
                <x-vdbs.loading-state label="Daten werden geladen" />

                <x-vdbs.loading-state
                    label="Mitgliederliste wird aktualisiert"
                    block
                />
            </div>
        </section>

        <section class="section stack">
            <h2>Button mit Busy-State</h2>

            <div class="design-example">
                <button class="btn" type="button" aria-busy="true">
                    <span class="btn__spinner" aria-hidden="true"></span>
                    Wird gespeichert
                </button>
            </div>
        </section>

        <section class="section stack">
            <h2>Bestimmter Fortschritt</h2>

            <div class="design-example">
                <div class="progress">
                    <div class="progress__label">
                        <span>Datei wird hochgeladen</span>
                        <span>65&nbsp;%</span>
                    </div>
                    <progress value="65" max="100">65 %</progress>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li><code>aria-busy="true"</code> kennzeichnet den betroffenen Bereich oder die Aktion.</li>
                <li>Live-Regions werden nur eingesetzt, wenn eine Statusänderung tatsächlich angekündigt werden soll.</li>
                <li>Bei reduzierter Bewegung rotiert der Spinner nicht.</li>
                <li>Unbestimmte Ladezustände enthalten keine erfundene Prozentangabe.</li>
            </ul>
        </section>
    </div>
@endsection
