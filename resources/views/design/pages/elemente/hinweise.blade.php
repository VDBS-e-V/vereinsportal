@extends('design.layout')

@section('title', 'Hinweise')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Feedback</p>
            <h1 class="page-title__title">Hinweise</h1>
            <p class="page-title__lead">
                Hinweise geben Rückmeldung zu einem Zustand oder Vorgang.
                Visuelle Bedeutung und ARIA-Verhalten werden getrennt behandelt.
            </p>
        </header>

        <section class="stack">
            <h2>Varianten</h2>

            <div class="design-example stack">
                <x-vdbs.notice type="info">
                    <p class="notice__title">Information</p>
                    <p class="notice__body">Sachlicher Hinweis ohne automatische Live-Region.</p>
                </x-vdbs.notice>

                <x-vdbs.notice type="success" role="status">
                    <p class="notice__title">Erfolgreich</p>
                    <p class="notice__body">Die Änderung wurde gespeichert.</p>
                </x-vdbs.notice>

                <x-vdbs.notice type="warning">
                    <p class="notice__title">Prüfen</p>
                    <p class="notice__body">Bitte kontrollieren Sie die Eingaben.</p>
                </x-vdbs.notice>

                <x-vdbs.notice type="danger" role="alert">
                    <p class="notice__title">Fehler</p>
                    <p class="notice__body">Der Vorgang konnte nicht abgeschlossen werden.</p>
                </x-vdbs.notice>
            </div>
        </section>

        <section class="section stack">
            <h2>Accessibility</h2>

            <ul>
                <li>Die sichtbare Variante bestimmt nicht automatisch die ARIA-Rolle.</li>
                <li><code>role="status"</code> wird nur für relevante, nicht dringende Live-Rückmeldungen verwendet.</li>
                <li><code>role="alert"</code> bleibt dringenden Meldungen vorbehalten.</li>
                <li>Statische Informationen benötigen normalerweise keine Live-Region.</li>
            </ul>
        </section>
    </div>
@endsection
