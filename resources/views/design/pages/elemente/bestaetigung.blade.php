@extends('design.layout')

@section('title', 'Bestätigung & Gefahr')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Feedback</p>
            <h1 class="page-title__title">Bestätigung &amp; Gefahr</h1>
            <p class="page-title__lead">
                Destruktive Aktionen erhalten einen klaren Kontext und vor der
                endgültigen Ausführung eine einfache Bestätigung.
            </p>
        </header>

        <section class="stack">
            <h2>Danger Zone</h2>

            <x-vdbs.danger-zone
                title="Mitgliedschaft beenden"
                description="Die Mitgliedschaft endet dauerhaft. Historische Daten bleiben entsprechend der fachlichen Regeln erhalten."
            >
                <x-slot:actions>
                    <button
                        class="btn btn--danger"
                        type="button"
                        data-vdbs-dialog-open="design-confirm-danger"
                    >
                        Mitgliedschaft beenden
                    </button>
                </x-slot:actions>
            </x-vdbs.danger-zone>

            <x-vdbs.dialog
                id="design-confirm-danger"
                title="Mitgliedschaft wirklich beenden?"
                description="Prüfen Sie die Auswirkung vor der endgültigen Ausführung."
                danger
            >
                <p>
                    Die Mitgliedschaft von Beispiel Person wird beendet.
                    Diese Aktion soll nicht versehentlich ausgelöst werden.
                </p>

                <x-slot:actions>
                    <button
                        class="btn btn--secondary"
                        type="button"
                        data-vdbs-dialog-close
                    >
                        Abbrechen
                    </button>
                    <button class="btn btn--danger" type="button">
                        Mitgliedschaft beenden
                    </button>
                </x-slot:actions>
            </x-vdbs.dialog>
        </section>

        <section class="section stack">
            <h2>Bestätigung ohne Zerstörung</h2>

            <p class="confirmation-note">
                Auch folgenreiche, aber nicht destruktive Aktionen dürfen vor dem
                Abschluss eine kurze Zusammenfassung zeigen.
            </p>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Keine Eingabe von Wörtern wie „LÖSCHEN“ als Standardbestätigung.</li>
                <li>Der Button benennt die konkrete Aktion statt nur „OK“ oder „Ja“.</li>
                <li>Abbrechen bleibt klar sichtbar und erhält keine Gefahrendarstellung.</li>
                <li>Gefahrendarstellung wird nicht für normale Sekundäraktionen verwendet.</li>
            </ul>
        </section>
    </div>
@endsection
