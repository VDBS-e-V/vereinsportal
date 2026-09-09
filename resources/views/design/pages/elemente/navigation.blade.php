@extends('design.layout')

@section('title', 'Lokale Navigation & Pagination')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Navigation</p>
            <h1 class="page-title__title">Lokale Navigation &amp; Pagination</h1>
            <p class="page-title__lead">
                Lokale Navigation ordnet Seiten innerhalb eines Bereichs.
                Seitenbasierte Tabs bleiben normale Links; Pagination navigiert
                zwischen Ergebnismengen oder Listen-Seiten.
            </p>
        </header>

        <section class="stack">
            <h2>Bereichsnavigation</h2>

            <nav class="local-nav" aria-label="Mitgliederverwaltung">
                <ul class="local-nav__list">
                    <li>
                        <a class="local-nav__link" href="#" aria-current="page">Übersicht</a>
                    </li>
                    <li>
                        <a class="local-nav__link" href="#">Mitglieder</a>
                    </li>
                    <li>
                        <a class="local-nav__link" href="#">Rollen</a>
                    </li>
                    <li>
                        <a class="local-nav__link" href="#">Import</a>
                    </li>
                </ul>
            </nav>
        </section>

        <section class="section stack">
            <h2>Seitenbasierte Tabs</h2>
            <p>
                Diese Tabs wechseln vollständige Seiten oder Ansichten.
                Deshalb werden bewusst normale Links und <code>aria-current</code>
                statt <code>role="tab"</code> verwendet.
            </p>

            <nav class="page-tabs" aria-label="Mitgliedsansicht">
                <ul class="page-tabs__list">
                    <li>
                        <a class="page-tabs__link" href="#" aria-current="page">Stammdaten</a>
                    </li>
                    <li>
                        <a class="page-tabs__link" href="#">Mitgliedschaft</a>
                    </li>
                    <li>
                        <a class="page-tabs__link" href="#">Kommunikation</a>
                    </li>
                    <li>
                        <a class="page-tabs__link" href="#">Historie</a>
                    </li>
                </ul>
            </nav>
        </section>

        <section class="section stack">
            <h2>Pagination</h2>

            <nav class="pagination" aria-label="Seitennavigation">
                <p class="pagination__summary">Einträge 21–40 von 126</p>

                <ul class="pagination__list">
                    <li><a class="pagination__link" href="#">Zurück</a></li>
                    <li><a class="pagination__link" href="#">1</a></li>
                    <li><span class="pagination__current" aria-current="page">2</span></li>
                    <li><a class="pagination__link" href="#">3</a></li>
                    <li><a class="pagination__link" href="#">4</a></li>
                    <li><a class="pagination__link" href="#">Weiter</a></li>
                </ul>
            </nav>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Keine permanente globale Sidebar für Bereichsnavigation.</li>
                <li>Aktive Seiten werden zusätzlich zu Farbe mit <code>aria-current="page"</code> gekennzeichnet.</li>
                <li>Seitenbasierte Tabs sind Navigation, keine JavaScript-Tabpanels.</li>
                <li>Auf kleinen Viewports darf die horizontale Navigation scrollbar bleiben.</li>
                <li>Navigationselemente und Pagination werden beim Drucken ausgeblendet.</li>
            </ul>
        </section>
    </div>
@endsection
