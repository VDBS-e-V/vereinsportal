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
            <h2>Standardtabelle</h2>

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
