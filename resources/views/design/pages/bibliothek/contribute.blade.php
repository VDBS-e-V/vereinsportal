@extends('design.layout')

@section('title', 'Bibliothek erweitern')

@section('content')
    @php
        $registryExample = <<<'PHP'
[
    'id' => 'content-highlight',
    'name' => 'Content Highlight',
    'category' => 'content',
    'status' => 'experimental',
    'description' => 'Kurze Beschreibung des Einsatzzwecks.',
    'source' => 'resources/views/components/vdbs/content-highlight.blade.php',
    'files' => [
        'resources/views/components/vdbs/content-highlight.blade.php',
        'resources/css/vdbs/components/content-highlight.css',
    ],
    'tags' => ['content', 'highlight'],
    'usage' => [
        'Konkreten sinnvollen Anwendungsfall beschreiben.',
    ],
    'avoid' => [
        'Abgrenzen, wann ein anderes Muster besser passt.',
    ],
    'accessibility' => [
        'Konkrete Accessibility-Regel dokumentieren.',
    ],
],
PHP;

        $routeExample = <<<'PHP'
Route::view(
    '/elemente/content-highlight',
    'design.pages.elemente.content-highlight',
)
    ->name('elemente.content-highlight')
    ->defaults(
        'design_title',
        'Content Highlight',
    );
PHP;
    @endphp

    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Bibliothek · Mitmachen</p>
            <h1 class="page-title__title">Neue Inhalte selbst hinzufügen</h1>
            <p class="page-title__lead">
                Die Bibliothek ist so aufgebaut, dass neue Inhalte ohne
                Spezialwissen über den Katalog ergänzt werden können.
                Entscheidend ist: erst wiederverwenden, dann erweitern.
            </p>
        </header>

        <section class="stack">
            <h2>1. Entscheiden, was Sie hinzufügen</h2>

            <div class="grid grid--3col">
                <div class="card">
                    <h3>Bestehendes Element</h3>
                    <p>
                        Existiert die Komponente bereits, ergänzen Sie nur
                        Registry, Dokumentation oder ein Codebeispiel.
                    </p>
                </div>
                <div class="card">
                    <h3>Neue Komponente</h3>
                    <p>
                        Für einen neuen wiederverwendbaren UI-Baustein
                        verwenden Sie den Komponenten-Generator.
                    </p>
                </div>
                <div class="card">
                    <h3>Neues Muster / Content</h3>
                    <p>
                        Zusammengesetzte Fachmuster entstehen aus echten
                        Anwendungsfällen und werden als Muster dokumentiert.
                    </p>
                </div>
            </div>
        </section>

        <section class="stack">
            <h2>2. Komponente oder Muster anlegen</h2>

            <x-vdbs.code-example
                title="Neue Komponente zuerst nur prüfen"
                code="php artisan vdbs:make-component content-highlight --dry-run"
                language="CMD"
            />

            <x-vdbs.code-example
                title="Komponente erzeugen"
                code="php artisan vdbs:make-component content-highlight"
                language="CMD"
            />

            <x-vdbs.code-example
                title="Neues Muster"
                code="php artisan vdbs:make-pattern resource-teaser --dry-run"
                language="CMD"
            />

            <x-vdbs.notice type="warning">
                Die Generatoren erzeugen ein Gerüst. Danach müssen
                CSS-Import, Route, Registry-Eintrag, Inhalt und Tests
                bewusst geprüft beziehungsweise ergänzt werden.
            </x-vdbs.notice>
        </section>

        <section class="stack">
            <h2>3. Registry-Eintrag ergänzen</h2>

            <p>
                Öffnen Sie <code>config/web_content_library.php</code> und
                ergänzen Sie den Eintrag in <code>items</code>.
            </p>

            <x-vdbs.code-example
                title="Registry-Vorlage"
                :code="$registryExample"
                language="PHP"
            />
        </section>

        <section class="stack">
            <h2>4. Designsystem-Seite und Route ergänzen</h2>

            <p>
                Jede neue produktive Komponente oder jedes neue Muster sollte
                mindestens eine verständliche Vorschau, Einsatzregeln und
                später möglichst ein kopierbares Codebeispiel haben.
            </p>

            <x-vdbs.code-example
                title="Beispielroute"
                :code="$routeExample"
                language="PHP"
            />
        </section>

        <section class="stack">
            <h2>5. CSS importieren</h2>

            <p>
                Wenn eine neue CSS-Datei entstanden ist, muss sie einmalig in
                <code>resources/css/app.css</code> importiert werden.
            </p>

            <x-vdbs.code-example
                title="CSS-Import"
                code="@import './vdbs/components/content-highlight.css';"
                language="CSS"
            />
        </section>

        <section class="stack">
            <h2>6. Qualität prüfen</h2>

            <x-vdbs.code-example
                title="Bibliothek und Design prüfen"
                :code="'php artisan vdbs:library-check'.PHP_EOL.'php artisan test tests\Feature\Design'.PHP_EOL.'npm run build'.PHP_EOL.'git diff --check'"
                language="CMD"
            />

            <p>
                <code>vdbs:library-check</code> erkennt zusätzlich neue
                <code>resources/views/components/vdbs/*.blade.php</code> und
                <code>resources/css/vdbs/components/*.css</code>, die noch
                nicht in der Registry inventarisiert wurden.
            </p>
        </section>

        <section class="stack">
            <h2>Sonderfälle</h2>

            <div class="metadata-list">
                <div>
                    <dt>Neues Icon</dt>
                    <dd>
                        Nur einen neuen <code>@case</code> in
                        <code>icon.blade.php</code> ergänzen. Der Icon-Browser
                        findet das Icon anschließend automatisch.
                    </dd>
                </div>
                <div>
                    <dt>Neue Button-Variante</dt>
                    <dd>
                        Bestehendes Button-System erweitern, nicht eine neue
                        Button-Komponente anlegen. Falls die Variante im
                        Playground auswählbar sein soll, zusätzlich
                        <code>ButtonExampleBuilder</code> ergänzen.
                    </dd>
                </div>
                <div>
                    <dt>Nur redaktionelles Muster</dt>
                    <dd>
                        Keine Blade-Komponente erzwingen. Ein dokumentiertes
                        Pattern mit vorhandenen Bausteinen kann die bessere
                        Lösung sein.
                    </dd>
                </div>
            </div>
        </section>
    </div>
@endsection
