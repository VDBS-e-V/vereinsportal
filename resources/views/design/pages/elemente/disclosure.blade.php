@extends('design.layout')

@section('title', 'Aufklappbare Inhalte')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Inhalte</p>
            <h1 class="page-title__title">Aufklappbare Inhalte</h1>
            <p class="page-title__lead">
                Native <code>details</code>- und <code>summary</code>-Elemente werden für
                ergänzende Informationen eingesetzt, die nicht dauerhaft sichtbar sein müssen.
            </p>
        </header>

        <section class="stack">
            <h2>Standard</h2>

            <div class="design-example container--narrow">
                <details class="disclosure">
                    <summary class="disclosure__summary">
                        Technische Details anzeigen
                    </summary>

                    <div class="disclosure__content">
                        <p>
                            Diese Information ist hilfreich, aber für die primäre Aufgabe
                            nicht zwingend erforderlich.
                        </p>
                    </div>
                </details>

                <details class="disclosure">
                    <summary class="disclosure__summary">
                        Weitere Hinweise anzeigen
                    </summary>

                    <div class="disclosure__content">
                        <p>
                            Mehrere Disclosure-Elemente dürfen direkt aufeinander folgen,
                            wenn die Inhalte fachlich zusammengehören.
                        </p>
                    </div>
                </details>
            </div>
        </section>

        <section class="section stack">
            <h2>Geöffnet</h2>

            <div class="design-example container--narrow">
                <details class="disclosure" open>
                    <summary class="disclosure__summary">
                        Bereits geöffneter Zustand
                    </summary>

                    <div class="disclosure__content">
                        <p>
                            Der offene Zustand wird weiterhin vollständig durch das native
                            HTML-Element verwaltet.
                        </p>
                    </div>
                </details>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln und Accessibility</h2>

            <ul>
                <li>Für einfache Aufklappbereiche wird kein eigenes JavaScript verwendet.</li>
                <li><code>summary</code> beschreibt klar, welcher Inhalt geöffnet wird.</li>
                <li>Keine zusätzlichen ARIA-Rollen auf <code>details</code> oder <code>summary</code>.</li>
                <li>Wichtige Fehler, Pflichtinformationen oder primäre Aktionen werden nicht versteckt.</li>
                <li>Der Inhalt bleibt beim Drucken sichtbar.</li>
            </ul>
        </section>
    </div>
@endsection
