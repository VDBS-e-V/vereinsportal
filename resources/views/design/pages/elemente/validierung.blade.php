@extends('design.layout')

@section('title', 'Validierungsübersicht')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Feedback</p>
            <h1 class="page-title__title">Validierungsübersicht</h1>
            <p class="page-title__lead">
                Bei mehreren Formularfehlern kann eine Übersicht vor dem Formular
                zusätzlich zu den Fehlermeldungen direkt an den Feldern eingesetzt werden.
            </p>
        </header>

        <section class="stack">
            <h2>Fehlerübersicht</h2>

            <div class="design-example container--form stack">
                <x-vdbs.validation-summary
                    description="Es gibt zwei Felder, die korrigiert werden müssen."
                    role="alert"
                >
                    <li><a href="#validation-email">E-Mail-Adresse prüfen</a></li>
                    <li><a href="#validation-name">Nachname ergänzen</a></li>
                </x-vdbs.validation-summary>

                <form class="form">
                    <div class="form__field form__field--error">
                        <label class="form__label" for="validation-email">E-Mail-Adresse</label>
                        <input
                            class="form__control"
                            id="validation-email"
                            type="email"
                            value="ungueltig"
                            aria-invalid="true"
                            aria-describedby="validation-email-error"
                        >
                        <p class="form__error" id="validation-email-error">
                            Bitte geben Sie eine gültige E-Mail-Adresse ein.
                        </p>
                    </div>

                    <div class="form__field form__field--error">
                        <label class="form__label" for="validation-name">Nachname</label>
                        <input
                            class="form__control"
                            id="validation-name"
                            type="text"
                            aria-invalid="true"
                            aria-describedby="validation-name-error"
                        >
                        <p class="form__error" id="validation-name-error">
                            Bitte ergänzen Sie den Nachnamen.
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Die Übersicht ersetzt niemals die Fehlermeldung direkt am Feld.</li>
                <li>Einträge verlinken auf die betroffenen Felder.</li>
                <li>Nach serverseitiger Validierung kann der Fokus auf die Übersicht gesetzt werden.</li>
                <li>Eine Alert-Rolle wird nur verwendet, wenn die Übersicht neu als Ergebnis einer Aktion erscheint.</li>
            </ul>
        </section>
    </div>
@endsection
