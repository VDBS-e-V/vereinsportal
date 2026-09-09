@extends('design.layout')

@section('title', 'Leere Zustände')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Feedback</p>
            <h1 class="page-title__title">Leere Zustände</h1>
            <p class="page-title__lead">
                Ein leerer Zustand erklärt, warum gerade keine Inhalte sichtbar sind,
                und bietet im Regelfall eine sinnvolle nächste Aktion an.
            </p>
        </header>

        <section class="stack">
            <h2>Noch keine Daten</h2>

            <div class="design-example">
                <x-vdbs.empty-state
                    title="Noch keine Mitglieder vorhanden"
                    description="Legen Sie das erste Mitglied an, um die Mitgliederverwaltung zu starten."
                    :heading-level="3"
                >
                    <x-slot:actions>
                        <a class="btn" href="#">Mitglied hinzufügen</a>
                    </x-slot:actions>
                </x-vdbs.empty-state>
            </div>
        </section>

        <section class="section stack">
            <h2>Keine Suchergebnisse</h2>

            <div class="design-example">
                <x-vdbs.empty-state
                    title="Keine passenden Ergebnisse"
                    description="Ändern Sie den Suchbegriff oder setzen Sie die aktiven Filter zurück."
                    :heading-level="3"
                    compact
                >
                    <x-slot:actions>
                        <button class="btn btn--secondary" type="button">Filter zurücksetzen</button>
                    </x-slot:actions>
                </x-vdbs.empty-state>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>

            <ul>
                <li>Kurze, konkrete Beschreibung statt technischer Fehlermeldung.</li>
                <li>Wenn eine sinnvolle nächste Aktion existiert, wird sie direkt angeboten.</li>
                <li>Keine dekorative Illustration als Pflichtbestandteil.</li>
                <li>Ein Empty State ersetzt keine Fehler- oder Berechtigungsnachricht.</li>
            </ul>
        </section>
    </div>
@endsection
