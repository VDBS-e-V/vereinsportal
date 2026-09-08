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
                <div class="notice notice--info">
                    <strong>Information:</strong> sachlicher Hinweis.
                </div>
                <div class="notice notice--success">
                    <strong>Erfolgreich:</strong> Änderung gespeichert.
                </div>
                <div class="notice notice--warning">
                    <strong>Prüfen:</strong> Eingaben kontrollieren.
                </div>
                <div class="notice notice--danger">
                    <strong>Fehler:</strong> Vorgang nicht abgeschlossen.
                </div>
            </div>
        </section>
    </div>
@endsection
