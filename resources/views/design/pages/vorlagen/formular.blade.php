@extends('design.layout')

@section('title', 'Create/Edit-Formular')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Verwaltung</p>
            <h1 class="page-title__title">Create/Edit-Formular</h1>
            <p class="page-title__lead">
                Für neue und bestehende Datensätze mit verständlicher Feldstruktur,
                Validierung und eindeutigen Abschlussaktionen.
            </p>
        </header>

        <div class="design-example container--form stack stack--lg">
            <header class="page-title">
                <p class="page-title__kicker">Mitgliederverwaltung</p>
                <h2 class="page-title__title">Mitglied bearbeiten</h2>
                <p class="page-title__lead">Pflichtfelder sind direkt am jeweiligen Feld gekennzeichnet.</p>
            </header>

            <x-vdbs.validation-summary
                description="Beispiel für eine serverseitige Validierungsrückmeldung."
            >
                <li><a href="#template-form-email">E-Mail-Adresse prüfen</a></li>
            </x-vdbs.validation-summary>

            <form class="form">
                <div class="form__grid form__grid--2">
                    <div class="form__field">
                        <label class="form__label" for="template-form-first-name">
                            Vorname <span class="form__required">*</span>
                        </label>
                        <input class="form__control" id="template-form-first-name" type="text" value="Erika">
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="template-form-last-name">
                            Nachname <span class="form__required">*</span>
                        </label>
                        <input class="form__control" id="template-form-last-name" type="text" value="Muster">
                    </div>
                </div>

                <div class="form__field form__field--error">
                    <label class="form__label" for="template-form-email">E-Mail-Adresse</label>
                    <input
                        class="form__control"
                        id="template-form-email"
                        type="email"
                        value="ungueltig"
                        aria-invalid="true"
                        aria-describedby="template-form-email-error"
                    >
                    <p class="form__error" id="template-form-email-error">
                        Bitte geben Sie eine gültige E-Mail-Adresse ein.
                    </p>
                </div>

                <div class="form__field">
                    <label class="form__label" for="template-form-role">Rolle</label>
                    <select class="form__control" id="template-form-role">
                        <option>Mitglied</option>
                        <option>Teamende</option>
                    </select>
                </div>

                <fieldset class="form__fieldset">
                    <legend class="form__legend">Kommunikation</legend>
                    <div class="form__choice">
                        <input id="template-form-news" type="checkbox">
                        <label for="template-form-news">Informationen per E-Mail erhalten</label>
                    </div>
                </fieldset>

                <div class="button-group">
                    <button class="btn" type="submit">Änderungen speichern</button>
                    <a class="btn btn--quiet" href="#">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
@endsection
