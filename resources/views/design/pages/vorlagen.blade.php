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

        <section class="section stack">
            <h2>Artikelseite</h2>
            <div class="design-example">
                <article class="article">
                    <header class="article__header">
                        <p class="article__kicker">Aktuelles</p>
                        <h3 class="article__title">Beispiel für eine redaktionelle Seite</h3>
                        <p class="article__lead">
                            Einleitung, Metadaten und Textkörper bilden gemeinsam die lesbare Grundstruktur.
                        </p>
                        <ul class="article__meta">
                            <li>09.09.2026</li>
                            <li>Redaktion VDBS</li>
                        </ul>
                    </header>
                    <div class="article__body">
                        <p>
                            Der eigentliche Inhalt bleibt auf eine angenehme Textbreite begrenzt.
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Veranstaltungsdetail</h2>
            <div class="design-example stack">
                <header class="page-title">
                    <p class="page-title__kicker">Veranstaltung</p>
                    <h3 class="page-title__title">Mitgliederversammlung 2026</h3>
                    <p class="page-title__lead">
                        Terminbeschreibung und wesentliche Informationen auf einen Blick.
                    </p>
                </header>

                <div class="event-detail-meta">
                    <div class="event-detail-meta__item">
                        <span class="event-detail-meta__label">Datum</span>
                        <span class="event-detail-meta__value">12.11.2026</span>
                    </div>
                    <div class="event-detail-meta__item">
                        <span class="event-detail-meta__label">Zeit</span>
                        <span class="event-detail-meta__value">18:30 Uhr</span>
                    </div>
                    <div class="event-detail-meta__item">
                        <span class="event-detail-meta__label">Ort</span>
                        <span class="event-detail-meta__value">Berlin</span>
                    </div>
                </div>

                <div class="button-group">
                    <button class="btn" type="button">Anmelden</button>
                    <a class="btn btn--secondary" href="#">Termin speichern</a>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Datensatzdetail</h2>
            <div class="design-example stack stack--lg">
                <header class="page-title page-title--split">
                    <div class="stack stack--sm">
                        <p class="page-title__kicker">Mitgliederverwaltung</p>
                        <h3 class="page-title__title">Erika Muster</h3>
                        <p class="page-title__lead">Mitgliedsnummer VDBS-10428</p>
                    </div>
                    <div class="page-title__actions">
                        <button class="btn btn--secondary" type="button">Bearbeiten</button>
                    </div>
                </header>

                <nav class="page-tabs" aria-label="Mitgliedsansicht">
                    <ul class="page-tabs__list">
                        <li><a class="page-tabs__link" href="#" aria-current="page">Stammdaten</a></li>
                        <li><a class="page-tabs__link" href="#">Mitgliedschaft</a></li>
                        <li><a class="page-tabs__link" href="#">Historie</a></li>
                    </ul>
                </nav>

                <dl class="metadata-list">
                    <div><dt>Status</dt><dd><x-vdbs.status type="success">Aktiv</x-vdbs.status></dd></div>
                    <div><dt>E-Mail</dt><dd>erika.muster@example.test</dd></div>
                    <div><dt>Eintritt</dt><dd>12.03.2021</dd></div>
                </dl>
            </div>
        </section>
    </div>
@endsection
