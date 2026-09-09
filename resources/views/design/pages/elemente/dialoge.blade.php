@extends('design.layout')

@section('title', 'Dialoge & Overlays')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Overlays</p>
            <h1 class="page-title__title">Dialoge &amp; Overlays</h1>
            <p class="page-title__lead">
                Modale Dialoge unterbrechen eine Aufgabe nur dann, wenn eine
                Entscheidung oder fokussierte Interaktion erforderlich ist.
            </p>
        </header>

        <section class="stack">
            <h2>Standarddialog</h2>

            <div class="design-example button-group">
                <button
                    class="btn"
                    type="button"
                    data-vdbs-dialog-open="design-info-dialog"
                >
                    Dialog öffnen
                </button>
            </div>

            <x-vdbs.dialog
                id="design-info-dialog"
                title="Änderung veröffentlichen?"
                description="Die neue Fassung wird anschließend für berechtigte Personen sichtbar."
            >
                <p>
                    Prüfen Sie vor der Veröffentlichung, ob Titel und Inhalte vollständig sind.
                </p>

                <x-slot:actions>
                    <button
                        class="btn btn--secondary"
                        type="button"
                        data-vdbs-dialog-close
                    >
                        Abbrechen
                    </button>
                    <button class="btn" type="button">Veröffentlichen</button>
                </x-slot:actions>
            </x-vdbs.dialog>
        </section>

        <section class="section stack">
            <h2>Gefahrendialog</h2>

            <button
                class="btn btn--danger"
                type="button"
                data-vdbs-dialog-open="design-delete-dialog"
            >
                Eintrag entfernen
            </button>

            <x-vdbs.dialog
                id="design-delete-dialog"
                title="Eintrag entfernen?"
                description="Diese Aktion kann nicht über den Dialog rückgängig gemacht werden."
                danger
            >
                <p>Der ausgewählte Eintrag wird dauerhaft entfernt.</p>

                <x-slot:actions>
                    <button
                        class="btn btn--secondary"
                        type="button"
                        data-vdbs-dialog-close
                    >
                        Abbrechen
                    </button>
                    <button class="btn btn--danger" type="button">Eintrag entfernen</button>
                </x-slot:actions>
            </x-vdbs.dialog>
        </section>

        <section class="section stack">
            <h2>Regeln und Accessibility</h2>
            <ul>
                <li>Die Implementierung nutzt das native <code>dialog</code>-Element und <code>showModal()</code>.</li>
                <li>Escape schließt native modale Dialoge.</li>
                <li>Nach dem Schließen kehrt der Fokus zum auslösenden Element zurück.</li>
                <li>Jeder Dialog besitzt einen sichtbaren Titel und eine erreichbare Schließen-Aktion.</li>
                <li>Dialoge werden nicht als Ersatz für normale Seiten oder lange Formulare verwendet.</li>
            </ul>
        </section>
    </div>
@endsection
