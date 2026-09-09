@extends('design.layout')

@section('title', 'Fehlerseiten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Vorlage · System</p>
            <h1 class="page-title__title">Fehlerseiten</h1>
            <p class="page-title__lead">
                Fehlerseiten erklären knapp, was passiert ist, und bieten einen
                sinnvollen nächsten Schritt statt technischer Detailmeldungen.
            </p>
        </header>

        <section class="stack">
            <h2>404 · Nicht gefunden</h2>
            <div class="design-example">
                <section class="error-state">
                    <p class="error-state__code">404</p>
                    <h3 class="error-state__title">Seite nicht gefunden</h3>
                    <p class="error-state__description">
                        Die angeforderte Seite ist nicht verfügbar oder wurde verschoben.
                    </p>
                    <div class="error-state__actions">
                        <a class="btn" href="#">Zur Übersicht</a>
                        <a class="btn btn--secondary" href="#">Zurück</a>
                    </div>
                </section>
            </div>
        </section>

        <section class="section stack">
            <h2>403 · Kein Zugriff</h2>
            <div class="design-example">
                <section class="error-state">
                    <p class="error-state__code">403</p>
                    <h3 class="error-state__title">Kein Zugriff auf diese Seite</h3>
                    <p class="error-state__description">
                        Für diesen Bereich fehlen die erforderlichen Berechtigungen.
                    </p>
                    <div class="error-state__actions">
                        <a class="btn" href="#">Zur Übersicht</a>
                    </div>
                </section>
            </div>
        </section>

        <section class="section stack">
            <h2>500 · Technischer Fehler</h2>
            <div class="design-example">
                <section class="error-state error-state--danger">
                    <p class="error-state__code">500</p>
                    <h3 class="error-state__title">Die Anfrage konnte nicht abgeschlossen werden</h3>
                    <p class="error-state__description">
                        Bitte versuchen Sie es später erneut. Interne technische Details
                        werden nicht auf der öffentlichen Fehlerseite ausgegeben.
                    </p>
                    <div class="error-state__actions">
                        <a class="btn" href="#">Zur Übersicht</a>
                        <button class="btn btn--secondary" type="button">Erneut versuchen</button>
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection
