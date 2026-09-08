@extends('design.layout')

@section('title', 'Elemente')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Web Components</p>
            <h1 class="page-title__title">Elemente</h1>
            <p class="page-title__lead">
                Die Bausteine sind funktional, eckig und zurückhaltend. Struktur entsteht
                über Typografie, Linien, Weißraum und wenige gezielte Akzentfarben.
            </p>
        </header>

        <section class="stack">
            <h2>Buttons</h2>
            <p>
                Primäre Aktionen erhalten die höchste Betonung. Sekundäre und ruhige
                Aktionen bleiben visuell zurückhaltender; destruktive Aktionen sind
                eindeutig als Gefahr gekennzeichnet.
            </p>

            <div class="design-example stack">
                <div class="button-group">
                    <button class="btn" type="button">Primäre Aktion</button>
                    <button class="btn btn--secondary" type="button">Sekundäre Aktion</button>
                    <button class="btn btn--accent" type="button">Unterstützende Aktion</button>
                    <a class="btn btn--quiet" href="#">Textaktion</a>
                    <button class="btn btn--danger" type="button">Löschen</button>
                </div>

                <div class="button-group">
                    <button class="btn btn--sm" type="button">Klein</button>
                    <button class="btn" type="button">Standard</button>
                    <button class="btn btn--lg" type="button">Groß</button>
                </div>

                <div class="button-group">
                    <button class="btn btn--icon" type="button" aria-label="Einstellungen">
                        <x-vdbs.icon name="settings" />
                    </button>

                    <button class="btn" type="button" aria-busy="true">
                        <span class="btn__spinner" aria-hidden="true"></span>
                        Wird gespeichert
                    </button>

                    <button class="btn" type="button" disabled>Deaktiviert</button>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Formulare</h2>
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

        <section class="section stack">
            <h2>Hinweise</h2>
            <div class="notice"><strong>Information:</strong> sachlicher Hinweis.</div>
            <div class="notice notice--success"><strong>Erfolgreich:</strong> Änderung gespeichert.</div>
            <div class="notice notice--warning"><strong>Prüfen:</strong> Eingaben kontrollieren.</div>
            <div class="notice notice--danger"><strong>Fehler:</strong> Vorgang nicht abgeschlossen.</div>
        </section>

        <section class="section stack">
            <h2>Teaser und Karten</h2>
            <p>
                Für Informationsübersichten sind lineare Teaser der Standard. Karten werden
                nur verwendet, wenn ein Inhalt tatsächlich eine eigenständige Einheit bildet.
            </p>

            <div class="teaser-list">
                <article class="teaser">
                    <span class="badge">Bereich</span>
                    <h3><a href="#">Mitgliedsdaten verwalten</a></h3>
                    <p>Kurze fachliche Beschreibung des Bereichs.</p>
                </article>
                <article class="teaser">
                    <span class="badge">Bereich</span>
                    <h3><a href="#">Kommunikation</a></h3>
                    <p>Vorlagen, Zustellungen und weitere Kommunikationsfunktionen.</p>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Tabelle</h2>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Bereich</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Beispiel A</td>
                            <td>Aktiv</td>
                            <td>Verwaltung</td>
                        </tr>
                        <tr>
                            <td>Beispiel B</td>
                            <td>Offen</td>
                            <td>Portal</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
