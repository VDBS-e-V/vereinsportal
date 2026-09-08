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
            <div class="design-example cluster">
                <button type="button">Primäre Aktion</button>
                <button class="btn btn--secondary" type="button">Sekundäre Aktion</button>
                <a class="btn btn--quiet" href="#">Textaktion</a>
                <button type="button" disabled>Deaktiviert</button>
            </div>
        </section>

        <section class="section stack">
            <h2>Formulare</h2>
            <div class="design-example container--form">
                <form class="form">
                    <div class="form__field">
                        <label class="form__label" for="design-name">Bezeichnung</label>
                        <input class="form__control" id="design-name" type="text" placeholder="Beispielwert">
                        <p class="form__help">Hilfetext steht direkt am Feld.</p>
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="design-select">Auswahl</label>
                        <select class="form__control" id="design-select">
                            <option>Option A</option>
                            <option>Option B</option>
                        </select>
                    </div>

                    <div class="form__field">
                        <label class="form__label" for="design-notes">Notiz</label>
                        <textarea class="form__control" id="design-notes"></textarea>
                    </div>
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
