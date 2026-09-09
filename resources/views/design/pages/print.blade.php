@extends('design.layout')

@section('title', 'Print')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Ausgabe</p>
            <h1 class="page-title__title">Print</h1>
            <p class="page-title__lead">
                Druckansichten reduzieren das Portal auf Inhalt, Struktur und
                fachlich relevante Metadaten. Navigation und interaktive Aktionen
                werden nicht mitgedruckt.
            </p>
        </header>

        <section class="stack">
            <h2>Druckbare Inhaltsseite</h2>

            <div class="design-example stack stack--lg">
                <p class="print-only">
                    Druckansicht · VDBS Portal
                </p>

                <article class="article">
                    <header class="article__header">
                        <p class="article__kicker">Aktuelles</p>
                        <h3 class="article__title">Beispiel für eine druckbare Inhaltsseite</h3>
                        <p class="article__lead">
                            Überschrift, Einleitung, Metadaten und Fließtext bleiben
                            auch ohne Portalnavigation verständlich.
                        </p>
                        <ul class="article__meta">
                            <li>09.09.2026</li>
                            <li>Redaktion VDBS</li>
                        </ul>
                    </header>

                    <div class="article__body">
                        <p>
                            Beim Drucken werden Schatten, Navigation, Filter,
                            Dialoge und Aktionsflächen entfernt.
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Tabellen und Datensätze</h2>

            <div class="design-example stack">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Erika Muster</td>
                                <td>Aktiv</td>
                                <td>09.09.2026</td>
                            </tr>
                            <tr>
                                <td>Max Beispiel</td>
                                <td>Ausstehend</td>
                                <td>12.09.2026</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Header, Footer, Breadcrumbs und interaktive Navigation werden ausgeblendet.</li>
                <li>Buttons, Filter, Pagination, Dialoge und destruktive Aktionen werden nicht gedruckt.</li>
                <li>Tabellenköpfe dürfen auf Folgeseiten wiederholt werden.</li>
                <li>Kontakte, Dateien, Ressourcen und Tabellenzeilen sollen nicht unnötig umbrechen.</li>
                <li>Geschlossene Details geben ihren Inhalt in der Druckansicht trotzdem aus.</li>
                <li><code>.print-only</code> und <code>.screen-only</code> steuern ausgabespezifische Inhalte.</li>
            </ul>
        </section>
    </div>
@endsection
