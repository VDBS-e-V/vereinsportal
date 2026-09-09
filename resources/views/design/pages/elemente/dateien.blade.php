@extends('design.layout')

@section('title', 'Dateien & Uploads')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Dateien</p>
            <h1 class="page-title__title">Dateien &amp; Uploads</h1>
            <p class="page-title__lead">
                Dateioberflächen zeigen Dateiname, Typ, Größe und Status klar an.
                Uploads bleiben native Dateifelder und werden nicht als künstliche Dropzone vorausgesetzt.
            </p>
        </header>

        <section class="stack">
            <h2>Datei hochladen</h2>

            <div class="file-upload">
                <label class="file-upload__label" for="design-file-upload">
                    Datei auswählen
                </label>
                <input
                    class="file-upload__input"
                    id="design-file-upload"
                    type="file"
                    accept=".pdf,.xlsx,.xls,.csv,.png,.jpg,.jpeg"
                >
                <p class="file-upload__help">
                    Unterstützt werden insbesondere PDF, Tabellen und Bilder.
                    Fachseiten definieren zusätzliche Größen- und Typgrenzen.
                </p>
            </div>
        </section>

        <section class="section stack">
            <h2>Dateiliste</h2>

            <div class="file-list">
                <x-vdbs.file-item
                    name="vereinssatzung-2026.pdf"
                    kind="PDF"
                    meta="1,8 MB · 09.09.2026"
                >
                    <x-slot:status>
                        <x-vdbs.status type="success">Verfügbar</x-vdbs.status>
                    </x-slot:status>
                    <x-slot:actions>
                        <a class="btn btn--secondary btn--sm" href="#">Herunterladen</a>
                    </x-slot:actions>
                </x-vdbs.file-item>

                <x-vdbs.file-item
                    name="mitglieder-export.xlsx"
                    kind="Tabelle"
                    meta="640 KB · 09.09.2026"
                >
                    <x-slot:status>
                        <x-vdbs.status type="info">Wird erzeugt</x-vdbs.status>
                    </x-slot:status>
                </x-vdbs.file-item>

                <x-vdbs.file-item
                    name="veranstaltungsfoto.jpg"
                    kind="Bild"
                    meta="3,2 MB · 08.09.2026"
                >
                    <x-slot:status>
                        <x-vdbs.status type="warning">Prüfung erforderlich</x-vdbs.status>
                    </x-slot:status>
                    <x-slot:actions>
                        <a class="btn btn--quiet btn--sm" href="#">Details</a>
                    </x-slot:actions>
                </x-vdbs.file-item>
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            <ul>
                <li>Dateiname und Dateityp bleiben als Text sichtbar.</li>
                <li>Upload-Felder nennen erlaubte Typen und fachliche Begrenzungen.</li>
                <li>Ein laufender Upload nutzt den gemeinsamen Loading-/Fortschrittsbaustein.</li>
                <li>Download-Links müssen keine dekorative Karte erzeugen.</li>
                <li>Fehlerhafte Uploads werden als Fehlerzustand mit konkreter Ursache ausgegeben.</li>
            </ul>
        </section>
    </div>
@endsection
