@extends('design.layout')

@section('title', 'Formulare')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Eingaben</p>
            <h1 class="page-title__title">Formulare</h1>
            <p class="page-title__lead">
                Formulare bleiben als semantisches HTML lesbar. Das Designsystem
                standardisiert Darstellung, Zustände und wiederkehrende Feldmuster.
            </p>
        </header>

        <section class="stack">
            <h2>Felder und Zustände</h2>

            <div class="design-example container--form">
                <form class="form">
                    <div class="form__field">
                        <label class="form__label" for="design-name">
                            Bezeichnung <span class="form__required" aria-hidden="true">*</span>
                        </label>
                        <input
                            class="form__control"
                            id="design-name"
                            type="text"
                            placeholder="Beispielwert"
                            required
                        >
                        <p class="form__help">Hilfetext steht direkt am Feld.</p>
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="design-select">
                            Auswahl <span class="form__optional">(optional)</span>
                        </label>
                        <select class="form__control" id="design-select">
                            <option>Option A</option>
                            <option>Option B</option>
                        </select>
                    </div>

                    <div class="form__field form__field--error">
                        <label class="form__label" for="design-error">Feld mit Fehler</label>
                        <input
                            class="form__control"
                            id="design-error"
                            type="text"
                            value="Ungültiger Wert"
                            aria-invalid="true"
                            aria-describedby="design-error-message"
                        >
                        <p class="form__error" id="design-error-message" role="alert">
                            Bitte prüfen Sie diesen Wert.
                        </p>
                    </div>

                    <div class="form__field form__field--success">
                        <label class="form__label" for="design-success">Erfolgreich geprüft</label>
                        <input
                            class="form__control"
                            id="design-success"
                            type="text"
                            value="Gültiger Wert"
                            aria-describedby="design-success-message"
                        >
                        <p class="form__status" id="design-success-message" role="status">
                            Der Wert ist gültig.
                        </p>
                    </div>

                    <div class="form__grid form__grid--2">
                        <div class="form__field">
                            <label class="form__label" for="design-readonly">Nur lesen</label>
                            <input
                                class="form__control"
                                id="design-readonly"
                                type="text"
                                value="Nicht bearbeitbar"
                                readonly
                            >
                        </div>

                        <div class="form__field">
                            <label class="form__label" for="design-disabled">Deaktiviert</label>
                            <input
                                class="form__control"
                                id="design-disabled"
                                type="text"
                                value="Nicht verfügbar"
                                disabled
                            >
                        </div>
                    </div>

                    <fieldset class="form__fieldset">
                        <legend class="form__legend">Benachrichtigungen</legend>

                        <div class="form__choice">
                            <input id="design-mail" type="checkbox">
                            <label for="design-mail">E-Mail-Benachrichtigungen</label>
                        </div>

                        <div class="form__choice">
                            <input id="design-post" name="design-channel" type="radio">
                            <label for="design-post">Postversand</label>
                        </div>
                    </fieldset>
                </form>
            </div>
        </section>
    </div>
@endsection
