@extends('layouts.administration')

@section('title', 'Übersicht')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Koordination</p>
            <h1 class="page-title__title">Koordinationsübersicht</h1>
            <p class="page-title__lead">Eigener Arbeitsbereich für die Koordination von Mitarbeitenden, Freiwilligen, Seminaren und Schulen.</p>
        </header>

        <x-vdbs.notice type="info" role="status">
            Der Bereich ist bereits eigenständig angelegt. Die Fachmodule für Mitarbeitende und Freiwillige, Seminare sowie Schulkoordination werden hier schrittweise ergänzt.
        </x-vdbs.notice>

        <section class="stack">
            <header class="stack stack--sm">
                <h2>Geplante Arbeitsbereiche</h2>
                <p>Diese Funktionen sind noch nicht freigeschaltet und erhalten bei ihrer Implementierung eigene Capabilities.</p>
            </header>
            <ul class="related-links">
                <li>Mitarbeitende &amp; Freiwillige</li>
                <li>Seminare</li>
                <li>Schulen / Schulkoordination</li>
            </ul>
        </section>
    </div>
@endsection
