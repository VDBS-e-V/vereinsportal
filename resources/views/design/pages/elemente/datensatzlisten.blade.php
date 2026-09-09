@extends('design.layout')

@section('title', 'Datensatzlisten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Daten</p>
            <h1 class="page-title__title">Datensatzlisten</h1>
            <p class="page-title__lead">
                Nicht jede strukturierte Datenmenge benötigt eine Tabelle.
                Lineare Datensatzlisten eignen sich für überschaubare Einträge mit wenigen Metadaten.
            </p>
        </header>

        <section class="stack">
            <h2>Standardliste</h2>

            <div class="record-list">
                <article class="record-item">
                    <div class="record-item__main">
                        <h3 class="record-item__title"><a href="#">Erika Muster</a></h3>
                        <div class="record-item__meta">
                            <span>VDBS-10428</span>
                            <span>Mitglied seit 12.03.2021</span>
                            <x-vdbs.status type="success">Aktiv</x-vdbs.status>
                        </div>
                    </div>
                    <div class="record-item__actions">
                        <a class="btn btn--secondary btn--sm" href="#">Öffnen</a>
                    </div>
                </article>

                <article class="record-item">
                    <div class="record-item__main">
                        <h3 class="record-item__title"><a href="#">Max Beispiel</a></h3>
                        <div class="record-item__meta">
                            <span>VDBS-10802</span>
                            <span>Mitglied seit 05.09.2024</span>
                            <x-vdbs.status type="warning">Ausstehend</x-vdbs.status>
                        </div>
                    </div>
                    <div class="record-item__actions">
                        <a class="btn btn--secondary btn--sm" href="#">Öffnen</a>
                    </div>
                </article>
            </div>
        </section>

        <section class="section stack">
            <h2>Wann Liste, wann Tabelle?</h2>
            <ul>
                <li>Datensatzliste: wenige Metadaten, primär ein Titel und eine Hauptaktion.</li>
                <li>Tabelle: mehrere vergleichbare Spalten, Sortierung oder hohe Informationsdichte.</li>
                <li>Beide Muster können lokale Suche, Filter und Pagination verwenden.</li>
            </ul>
        </section>
    </div>
@endsection
