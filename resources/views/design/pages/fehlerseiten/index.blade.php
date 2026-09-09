@extends('design.layout')

@section('title', 'Fehlerseiten')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Muster · Systemzustände</p>
            <h1 class="page-title__title">Fehlerseiten</h1>
            <p class="page-title__lead">
                Fehlerseiten bleiben ruhig, verständlich und handlungsorientiert.
                Sie erklären kurz, was passiert ist, und führen mit einer klaren
                Hauptaktion zurück in einen sicheren Portal-Flow.
            </p>
        </header>

        <section class="stack">
            <h2>Gestaltungsprinzipien</h2>

            <div class="grid grid--3col">
                <div class="card">
                    <h3>Erklären statt codieren</h3>
                    <p>
                        Der technische Fehlercode bleibt sichtbar, steht aber
                        nicht allein. Titel und Beschreibung übersetzen die
                        Situation in verständliche Sprache.
                    </p>
                </div>

                <div class="card">
                    <h3>Eine klare Hauptaktion</h3>
                    <p>
                        Jede Seite bietet einen eindeutigen Rückweg. Weitere
                        Aktionen sind bewusst nachgeordnet und auf das Nötige
                        begrenzt.
                    </p>
                </div>

                <div class="card">
                    <h3>Markenkonform bleiben</h3>
                    <p>
                        Typografie, Farben, 4-px-Rhythmus und kantige Flächen
                        bleiben Teil des VDBS-Systems. Humor bleibt dezent und
                        weicht in sicherheitsrelevanten Fällen der Klarheit.
                    </p>
                </div>
            </div>
        </section>

        <section class="stack stack--lg">
            <header class="stack stack--sm">
                <p class="page-title__kicker">Standard</p>
                <h2>404 · Seite nicht gefunden</h2>
            </header>

            <div class="design-example">
                <x-vdbs.error-page
                    code="404"
                    title="Seite nicht gefunden"
                    description="Die gewünschte Seite konnte nicht gefunden werden. Möglicherweise wurde sie verschoben oder die Adresse ist nicht mehr aktuell."
                    support="Von hier aus gelangen Sie schnell zurück in das Portal."
                    variant="info"
                    primary-label="Zur Startseite"
                    primary-url="#"
                />
            </div>
        </section>

        <section class="stack stack--lg">
            <header class="stack stack--sm">
                <p class="page-title__kicker">Varianten</p>
                <h2>Weitere Fehlerzustände</h2>
            </header>

            <div class="stack stack--lg">
                <div class="design-example">
                    <x-vdbs.error-page
                        code="403"
                        title="Zugriff nicht erlaubt"
                        description="Sie haben derzeit keine Berechtigung, diese Seite aufzurufen."
                        support="Wechseln Sie zurück zur Übersicht oder melden Sie sich mit einem anderen Konto an."
                        variant="warning"
                        primary-label="Zur Übersicht"
                        primary-url="#"
                        secondary-label="Zur Anmeldung"
                        secondary-url="#"
                        compact
                    />
                </div>

                <div class="design-example">
                    <x-vdbs.error-page
                        code="419"
                        title="Sitzung abgelaufen"
                        description="Aus Sicherheitsgründen ist Ihre Sitzung abgelaufen. Bitte melden Sie sich erneut an."
                        support="Nicht gespeicherte Eingaben müssen gegebenenfalls erneut vorgenommen werden."
                        variant="warning"
                        primary-label="Erneut anmelden"
                        primary-url="#"
                        compact
                    />
                </div>

                <div class="design-example">
                    <x-vdbs.error-page
                        code="500"
                        title="Es ist ein Fehler aufgetreten"
                        description="Beim Laden der Seite ist ein unerwarteter technischer Fehler aufgetreten."
                        support="Wenn der Fehler bestehen bleibt, wenden Sie sich bitte an den Support."
                        variant="danger"
                        primary-label="Zur Startseite"
                        primary-url="#"
                        secondary-label="Erneut versuchen"
                        secondary-url="#"
                        compact
                    />
                </div>

                <div class="design-example">
                    <x-vdbs.error-page
                        code="503"
                        title="Vorübergehend nicht verfügbar"
                        description="Dieser Bereich ist aktuell vorübergehend nicht verfügbar."
                        support="Bitte versuchen Sie es in einigen Minuten erneut."
                        variant="warning"
                        primary-label="Erneut versuchen"
                        primary-url="#"
                        secondary-label="Zur Startseite"
                        secondary-url="#"
                        compact
                    />
                </div>
            </div>
        </section>

        <section class="stack">
            <h2>Inhaltsregeln</h2>

            <div class="metadata-list">
                <div>
                    <dt>Fehlercode</dt>
                    <dd>Bleibt sichtbar, ist aber nie die einzige Erklärung.</dd>
                </div>
                <div>
                    <dt>Titel</dt>
                    <dd>Beschreibt die Situation in Alltagssprache.</dd>
                </div>
                <div>
                    <dt>Beschreibung</dt>
                    <dd>Maximal zwei kurze Sätze ohne technische Interna.</dd>
                </div>
                <div>
                    <dt>Aktionen</dt>
                    <dd>Eine Hauptaktion, höchstens eine zusätzliche Nebenaktion.</dd>
                </div>
                <div>
                    <dt>Ton</dt>
                    <dd>Freundlich und ruhig; bei Sicherheit und Verwaltung sachlich.</dd>
                </div>
            </div>
        </section>
    </div>
@endsection
