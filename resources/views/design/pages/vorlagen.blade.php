@extends('design.layout')

@section('title', 'Vorlagen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Seitenstruktur</p>
            <h1 class="page-title__title">Vorlagen</h1>
            <p class="page-title__lead">
                Die Portalstruktur arbeitet mit horizontaler Hauptnavigation, Dropdowns für
                weitere Ebenen und Breadcrumbs für tiefe Hierarchien. Eine dauerhafte linke
                Seitennavigation gehört nicht zum Standardlayout.
            </p>
        </header>

        <section class="stack">
            <h2>Inhaltsseite</h2>
            <div class="design-example stack stack--lg">
                <header class="page-title">
                    <p class="page-title__kicker">VDBS Portal</p>
                    <h1 class="page-title__title">Über das Portal</h1>
                    <p class="page-title__lead">Kurze Einleitung und klare Orientierung zum Inhalt.</p>
                </header>

                <div class="container--narrow stack">
                    <h2>Abschnitt</h2>
                    <p>
                        Inhalte stehen direkt auf der weißen Grundfläche. Zusätzliche Rahmen
                        werden nur dort eingesetzt, wo sie eine echte Gruppierung ausdrücken.
                    </p>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Formularseite</h2>
            <div class="design-example container--form stack stack--lg">
                <header class="page-title">
                    <p class="page-title__kicker">Konto</p>
                    <h1 class="page-title__title">Persönliche Daten</h1>
                    <p class="page-title__lead">Bearbeiten Sie die Angaben und speichern Sie die Änderungen.</p>
                </header>

                <form class="form">
                    <div class="form__field">
                        <label class="form__label" for="template-field">Feldbezeichnung</label>
                        <input class="form__control" id="template-field" type="text">
                    </div>
                    <div class="cluster">
                        <button type="button">Speichern</button>
                        <a class="btn btn--quiet" href="#">Abbrechen</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="section stack">
            <h2>Verwaltungsübersicht</h2>
            <div class="design-example stack stack--lg">
                <header class="page-title page-title--split">
                    <div class="stack stack--sm">
                        <p class="page-title__kicker">Verwaltung</p>
                        <h1 class="page-title__title">Mitglieder</h1>
                        <p class="page-title__lead">Übersicht über vorhandene Datensätze.</p>
                    </div>
                    <div class="page-title__actions">
                        <button type="button">Neu erstellen</button>
                    </div>
                </header>

                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Status</th><th>Aktion</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Erika Muster</td><td>Aktiv</td><td><a href="#">Öffnen</a></td></tr>
                            <tr><td>Max Beispiel</td><td>Aktiv</td><td><a href="#">Öffnen</a></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
