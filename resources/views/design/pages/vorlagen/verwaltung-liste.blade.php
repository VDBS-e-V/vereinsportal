@extends('design.layout')

@section('title', 'Verwaltungs-Liste')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · Verwaltung</p>
            <h1 class="page-title__title">Verwaltungs-Liste</h1>
            <p class="page-title__lead">
                Für größere Datenmengen mit Suche, optionalen Filtern, Sortierung,
                Aktionen und Seitennavigation.
            </p>
        </header>

        <div class="design-example stack stack--lg">
            <header class="page-title page-title--split">
                <div class="stack stack--sm">
                    <p class="page-title__kicker">Mitgliederverwaltung</p>
                    <h2 class="page-title__title">Mitglieder</h2>
                    <p class="page-title__lead">Übersicht über vorhandene Mitgliedsdatensätze.</p>
                </div>
                <div class="page-title__actions">
                    <button class="btn" type="button">Mitglied hinzufügen</button>
                </div>
            </header>

            <form class="search-form" action="#" method="get">
                <div class="search-form__field">
                    <label for="template-list-search">Mitglieder durchsuchen</label>
                    <input
                        class="form__control"
                        id="template-list-search"
                        type="search"
                        placeholder="Name, E-Mail oder Mitgliedsnummer"
                    >
                </div>
                <div class="search-form__actions">
                    <button class="btn btn--secondary" type="submit">Suchen</button>
                </div>
            </form>

            <form class="filter-bar" action="#" method="get">
                <div class="filter-bar__fields">
                    <div class="filter-bar__field">
                        <label for="template-list-status">Status</label>
                        <select class="form__control" id="template-list-status">
                            <option>Alle</option>
                            <option>Aktiv</option>
                            <option>Ausstehend</option>
                        </select>
                    </div>
                    <div class="filter-bar__field">
                        <label for="template-list-role">Rolle</label>
                        <select class="form__control" id="template-list-role">
                            <option>Alle Rollen</option>
                            <option>Mitglied</option>
                            <option>Teamende</option>
                        </select>
                    </div>
                </div>
                <div class="filter-bar__actions">
                    <button class="btn btn--secondary" type="submit">Filter anwenden</button>
                    <a class="btn btn--quiet" href="#">Zurücksetzen</a>
                </div>
            </form>

            <div class="table-toolbar">
                <div class="table-toolbar__primary">
                    <span class="table-toolbar__summary">126 Mitglieder</span>
                </div>
                <div class="table-toolbar__secondary">
                    <button class="btn btn--secondary" type="button">Exportieren</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th aria-sort="ascending">
                                <button class="table-sort" type="button">Name ↑</button>
                            </th>
                            <th>Status</th>
                            <th>Mitgliedsnummer</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Erika Muster</td>
                            <td><x-vdbs.status type="success">Aktiv</x-vdbs.status></td>
                            <td>VDBS-10428</td>
                            <td class="table__actions"><a href="#">Öffnen</a></td>
                        </tr>
                        <tr>
                            <td>Max Beispiel</td>
                            <td><x-vdbs.status type="warning">Ausstehend</x-vdbs.status></td>
                            <td>VDBS-10802</td>
                            <td class="table__actions"><a href="#">Öffnen</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav class="pagination" aria-label="Seitennavigation">
                <p class="pagination__summary">Einträge 1–20 von 126</p>
                <ul class="pagination__list">
                    <li><span class="pagination__disabled">Zurück</span></li>
                    <li><span class="pagination__current" aria-current="page">1</span></li>
                    <li><a class="pagination__link" href="#">2</a></li>
                    <li><a class="pagination__link" href="#">3</a></li>
                    <li><a class="pagination__link" href="#">Weiter</a></li>
                </ul>
            </nav>
        </div>
    </div>
@endsection
