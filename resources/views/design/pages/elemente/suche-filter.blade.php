@extends('design.layout')

@section('title', 'Suche & Filter')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Daten</p>
            <h1 class="page-title__title">Suche &amp; Filter</h1>
            <p class="page-title__lead">
                Verwaltungsseiten können lokale Suche und Filter unabhängig
                voneinander verwenden. Nur tatsächlich benötigte Funktionen werden angezeigt.
            </p>
        </header>

        <section class="stack">
            <h2>Lokale Suche</h2>

            <form class="search-form" action="#" method="get">
                <div class="search-form__field">
                    <label for="design-member-search">Mitglieder durchsuchen</label>
                    <input
                        class="form__control"
                        id="design-member-search"
                        name="q"
                        type="search"
                        placeholder="Name, E-Mail oder Mitgliedsnummer"
                    >
                </div>

                <div class="search-form__actions">
                    <button class="btn" type="submit">Suchen</button>
                    <a class="btn btn--quiet" href="#">Zurücksetzen</a>
                </div>
            </form>
        </section>

        <section class="section stack">
            <h2>Filter</h2>

            <form class="filter-bar" action="#" method="get">
                <div class="filter-bar__fields">
                    <div class="filter-bar__field">
                        <label for="design-status-filter">Status</label>
                        <select class="form__control" id="design-status-filter" name="status">
                            <option>Alle</option>
                            <option>Aktiv</option>
                            <option>Ausstehend</option>
                            <option>Gesperrt</option>
                        </select>
                    </div>

                    <div class="filter-bar__field">
                        <label for="design-role-filter">Rolle</label>
                        <select class="form__control" id="design-role-filter" name="role">
                            <option>Alle Rollen</option>
                            <option>Mitglied</option>
                            <option>Teamende</option>
                            <option>Administration</option>
                        </select>
                    </div>
                </div>

                <div class="filter-bar__actions">
                    <button class="btn btn--secondary" type="submit">Filter anwenden</button>
                    <a class="btn btn--quiet" href="#">Filter zurücksetzen</a>
                    <span class="filter-bar__summary">2 Filter verfügbar</span>
                </div>
            </form>
        </section>

        <section class="section stack">
            <h2>Listen-Toolbar</h2>

            <div class="table-toolbar">
                <div class="table-toolbar__primary">
                    <span class="table-toolbar__summary">126 Mitglieder</span>
                </div>

                <div class="table-toolbar__secondary">
                    <button class="btn btn--secondary" type="button">Exportieren</button>
                    <button class="btn" type="button">Mitglied hinzufügen</button>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Suche und Filter sind pro Fachseite optional.</li>
                <li>Lokale Suche betrifft nur die jeweilige Daten- oder Inhaltsmenge.</li>
                <li>Filter bleiben sichtbar beschriftete Formfelder.</li>
                <li>Resultate werden textlich zusammengefasst; Farbe ist nicht erforderlich.</li>
                <li>Bulk-Aktionen und erweiterte Tabellenwerkzeuge bleiben eine spätere Ausbaustufe.</li>
            </ul>
        </section>
    </div>
@endsection
