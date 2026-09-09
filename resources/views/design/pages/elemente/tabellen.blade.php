@extends('design.layout')

@section('title', 'Tabellen')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Daten</p>
            <h1 class="page-title__title">Tabellen</h1>
            <p class="page-title__lead">
                Tabellen bleiben semantische Tabellen. Auf schmalen Viewports
                werden sie zunächst horizontal scrollbar statt in Karten umgebaut.
            </p>
        </header>

        <section class="stack">
            <h2>Standardtabelle mit Toolbar</h2>

            <div class="table-toolbar">
                <div class="table-toolbar__primary">
                    <span class="table-toolbar__summary">3 Einträge</span>
                </div>

                <div class="table-toolbar__secondary">
                    <button class="btn" type="button">Eintrag hinzufügen</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th aria-sort="ascending">
                                <button class="table-sort" type="button">
                                    Name
                                    <span aria-hidden="true">↑</span>
                                </button>
                            </th>
                            <th>Status</th>
                            <th>Bereich</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Beispiel A</td>
                            <td><x-vdbs.status type="success">Aktiv</x-vdbs.status></td>
                            <td>Verwaltung</td>
                            <td class="table__actions"><a href="#">Öffnen</a></td>
                        </tr>
                        <tr>
                            <td>Beispiel B</td>
                            <td><x-vdbs.status type="warning">Ausstehend</x-vdbs.status></td>
                            <td>Portal</td>
                            <td class="table__actions"><a href="#">Öffnen</a></td>
                        </tr>
                        <tr>
                            <td>Beispiel C</td>
                            <td><x-vdbs.status type="danger">Gesperrt</x-vdbs.status></td>
                            <td>Verwaltung</td>
                            <td class="table__actions"><a href="#">Öffnen</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Kompakte Tabelle</h2>

            <div class="table-wrapper">
                <table class="table table--compact">
                    <thead>
                        <tr>
                            <th>Feld</th>
                            <th>Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Mitgliedsnummer</td>
                            <td>VDBS-10428</td>
                        </tr>
                        <tr>
                            <td>Eintritt</td>
                            <td>09.09.2026</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>

            <ul>
                <li>Semantische Tabellenstruktur mit <code>thead</code>, <code>tbody</code> und echten Überschriften.</li>
                <li>Sortierung wird am Tabellenkopf mit <code>aria-sort</code> beschrieben.</li>
                <li>Such-, Filter- und Aktions-Toolbars sind optional.</li>
                <li>Auf kleinen Viewports bleibt horizontales Scrollen zunächst erlaubt.</li>
                <li>Tabellen bleiben beim Drucken vollständig sichtbar; Toolbars werden ausgeblendet.</li>
            </ul>
        </section>
    </div>
@endsection
