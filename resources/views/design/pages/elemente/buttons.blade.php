@extends('design.layout')

@section('title', 'Buttons')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Elemente · Aktionen</p>
            <h1 class="page-title__title">Buttons</h1>
            <p class="page-title__lead">
                Buttons kennzeichnen ausführbare Aktionen. Ihre visuelle Gewichtung
                folgt der Bedeutung der Aktion und nicht der gewünschten Dekoration.
            </p>
        </header>

        <section class="stack">
            <h2>Varianten</h2>
            <p>
                Primäre Aktionen erhalten die höchste Betonung. Sekundäre und ruhige
                Aktionen bleiben visuell zurückhaltender; destruktive Aktionen sind
                eindeutig als Gefahr gekennzeichnet.
            </p>

            <div class="design-example stack">
                <div class="button-group">
                    <button class="btn" type="button">Primäre Aktion</button>
                    <button class="btn btn--secondary" type="button">Sekundäre Aktion</button>
                    <button class="btn btn--accent" type="button">Unterstützende Aktion</button>
                    <a class="btn btn--quiet" href="#">Textaktion</a>
                    <button class="btn btn--danger" type="button">Löschen</button>
                </div>
            </div>
        </section>

        <section class="section stack">
            <h2>Größen</h2>

            <div class="design-example button-group">
                <button class="btn btn--sm" type="button">Klein</button>
                <button class="btn" type="button">Standard</button>
                <button class="btn btn--lg" type="button">Groß</button>
            </div>
        </section>

        <section class="section stack">
            <h2>Zustände und Icon-Button</h2>

            <div class="design-example button-group">
                <button class="btn btn--icon" type="button" aria-label="Einstellungen">
                    <x-vdbs.icon name="settings" />
                </button>

                <button class="btn" type="button" aria-busy="true">
                    <span class="btn__spinner" aria-hidden="true"></span>
                    Wird gespeichert
                </button>

                <button class="btn" type="button" disabled>Deaktiviert</button>
            </div>
        </section>
    </div>
@endsection
