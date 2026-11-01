@php
    $crumbs = [['label' => 'Start', 'href' => route('home')], ['label' => 'Design-Pakete']];
@endphp

<x-layouts.app title="Design-Pakete" width="medium">
    <x-vdbs.layout.page-header title="Design-Pakete entwickeln" lead="Anleitung für alle, die neue Komponenten für das Designsystem schreiben: Ordnerstruktur, Befehle, Regeln und Prüfungen." :breadcrumb="$crumbs" />

    <nav class="vdbs-prose" aria-labelledby="toc-heading">
        <h2 id="toc-heading" style="font-size:var(--font-size-h3)">Inhalt</h2>
        <ol>
            <li><a href="#ueberblick">Überblick</a></li>
            <li><a href="#schnellstart">Schnellstart</a></li>
            <li><a href="#struktur">Ordnerstruktur</a></li>
            <li><a href="#befehle">Die Befehle</a></li>
            <li><a href="#dateien">Was die Befehle anlegen</a></li>
            <li><a href="#arbeiten">So schreiben Sie eine Komponente</a></li>
            <li><a href="#regeln">Regeln für Code, CSS und Texte</a></li>
            <li><a href="#pruefen">Prüfen und testen</a></li>
            <li><a href="#fehler">Fehlerbehebung</a></li>
            <li><a href="#ausblick">Ausblick</a></li>
        </ol>
    </nav>

    <div class="vdbs-prose">
        <h2 id="ueberblick">1. Überblick</h2>
        <p>Das Designsystem des Portals besteht aus <strong>Paketen</strong>. Ein Paket bündelt Komponenten zu einem Thema, zum Beispiel Aktionen (Buttons), Formulare, Navigation oder Daten. Jede <strong>Komponente</strong> hat bis zu fünf Teile:</p>
        <ul>
            <li>die <strong>Blade-Komponente</strong> mit dem Markup (<code>&lt;x-vdbs.paket.komponente&gt;</code>),</li>
            <li>eine <strong>CSS-Datei</strong>, die nur Tokens aus <code>tokens.css</code> verwendet,</li>
            <li>eine <strong>Beispielseite</strong> in der Design-Bibliothek, die alle Varianten zeigt,</li>
            <li>einen <strong>Eintrag im Paket-Manifest</strong>, damit die Bibliothek die Seite findet,</li>
            <li>einen <strong>Test</strong>, der die Komponente rendert.</li>
        </ul>
        <p>Sie schreiben das Markup und das CSS. Alles andere erzeugen drei Befehle: <code>design:package</code>, <code>design:component</code> und <code>design:check</code>. Die Beispielseiten sind in der Design-Bibliothek unter <code>/design/&lt;paket&gt;-&lt;komponente&gt;</code> zu sehen. Diese Bibliothek ist nur für angemeldete Personen mit der Berechtigung <code>design.view</code> (Administration und Vorstand) zugänglich.</p>

        <h2 id="schnellstart">2. Schnellstart</h2>
        <p>Im Projektordner, in der Konsole:</p>
        @verbatim
<pre><code>php artisan design:package meine-karten --title="Meine Karten"
php artisan design:component meine-karten info-box --title="Info-Box" --props=icon
npm run build
php artisan design:check</code></pre>
        @endverbatim
        <ol>
            <li>Der erste Befehl legt das Paket an.</li>
            <li>Der zweite legt die Komponente <code>info-box</code> mit allen Dateien an.</li>
            <li><code>npm run build</code> baut das CSS neu (bei laufendem <code>npm run dev</code> nicht nötig).</li>
            <li><code>design:check</code> prüft, dass nichts fehlt.</li>
        </ol>
        <p>Danach öffnen Sie <code>/design/meine-karten-info-box</code> in der Design-Bibliothek, bearbeiten die beiden Dateien <code>info-box.blade.php</code> und <code>info-box.css</code> und laden die Seite neu.</p>

        <h2 id="struktur">3. Ordnerstruktur</h2>
        @verbatim
<pre><code>resources/views/components/vdbs/&lt;paket&gt;/&lt;komponente&gt;.blade.php          ← Ihr Markup
resources/css/vdbs/components/&lt;paket&gt;/index.css                       ← bindet alle CSS-Dateien des Pakets ein
resources/css/vdbs/components/&lt;paket&gt;/&lt;komponente&gt;.css                ← Ihr CSS
resources/css/app.css                                                  ← bindet index.css ein (automatisch)
resources/views/design-library/entries/&lt;paket&gt;-&lt;komponente&gt;.blade.php   ← Beispielseite
app/Modules/DesignLibrary/Packages/&lt;paket&gt;.php                         ← Manifest des Pakets
tests/Feature/DesignLibrary/Packages/&lt;Paket&gt;Test.php                   ← Tests des Pakets
stubs/design/*.stub                                                    ← Vorlagen der Befehle</code></pre>
        @endverbatim
        <p>Die Datei <code>resources/css/app.css</code> enthält dafür eine Markierung (<code>design-packages:imports</code>). Der Befehl <code>design:package</code> fügt seine Zeile direkt davor ein. Entfernen Sie die Markierung nicht.</p>

        <h2 id="befehle">4. Die Befehle</h2>

        <h3>design:package</h3>
        <p>Legt ein Paket an.</p>
        @verbatim
<pre><code>php artisan design:package &lt;name&gt; [--title=] [--description=] [--force] [--dry-run]</code></pre>
        @endverbatim
        <table>
            <thead><tr><th scope="col">Angabe</th><th scope="col">Bedeutung</th></tr></thead>
            <tbody>
                <tr><td><code>name</code></td><td>Name des Pakets, zum Beispiel <code>meine-karten</code> oder <code>Meine Karten</code>. Er wird in Kleinbuchstaben mit Bindestrichen umgeformt und muss mit einem Buchstaben beginnen.</td></tr>
                <tr><td><code>--title</code></td><td>Anzeigename für die Bibliothek. Ohne Angabe wird er aus dem Namen gebildet.</td></tr>
                <tr><td><code>--description</code></td><td>Kurze Beschreibung des Pakets.</td></tr>
                <tr><td><code>--force</code></td><td>Überschreibt ein vorhandenes Paket. Ohne diese Angabe bricht der Befehl ab, wenn es das Paket schon gibt.</td></tr>
                <tr><td><code>--dry-run</code></td><td>Zeigt nur, was entstehen würde, und legt nichts an.</td></tr>
            </tbody>
        </table>

        <h3>design:component</h3>
        <p>Legt eine Komponente in einem vorhandenen Paket an.</p>
        @verbatim
<pre><code>php artisan design:component &lt;paket&gt; &lt;name&gt; [--title=] [--description=] [--props=a,b] [--wide] [--force] [--dry-run]</code></pre>
        @endverbatim
        <table>
            <thead><tr><th scope="col">Angabe</th><th scope="col">Bedeutung</th></tr></thead>
            <tbody>
                <tr><td><code>paket</code></td><td>Das Paket muss es schon geben (siehe oben).</td></tr>
                <tr><td><code>name</code></td><td>Name der Komponente, zum Beispiel <code>info-box</code>. Daraus entstehen <code>&lt;x-vdbs.paket.info-box&gt;</code> und die CSS-Klasse <code>vdbs-info-box</code>.</td></tr>
                <tr><td><code>--title</code>, <code>--description</code></td><td>Titel und Beschreibung in der Design-Bibliothek.</td></tr>
                <tr><td><code>--props=icon,tone-name</code></td><td>Zusätzliche Eigenschaften der Komponente. Sie stehen danach in <code>@@props</code> (hier <code>icon</code> und <code>toneName</code>, mit Vorgabewert <code>null</code>).</td></tr>
                <tr><td><code>--wide</code></td><td>Die Beispielseite wird in der breiten Ansicht gezeigt (für Tabellen, Raster, Hero).</td></tr>
                <tr><td><code>--force</code></td><td>Überschreibt vorhandene Dateien der Komponente.</td></tr>
                <tr><td><code>--dry-run</code></td><td>Zeigt nur, was entstehen würde.</td></tr>
            </tbody>
        </table>
        <p>Existiert schon eine der drei Dateien der Komponente, bricht der Befehl ab, bevor er etwas anlegt. So bleibt kein halbes Ergebnis zurück.</p>

        <h3>design:check</h3>
        @verbatim
<pre><code>php artisan design:check [paket]</code></pre>
        @endverbatim
        <p>Prüft alle Pakete oder ein einzelnes. Der Befehl meldet fehlende Dateien (Komponente, CSS, Beispielseite, Test), fehlende Importe in <code>index.css</code> und <code>app.css</code>, Farbcodes und primitive Farb-Tokens im CSS sowie CSS ohne die Klasse <code>.vdbs-&lt;name&gt;</code>. Bei Problemen endet er mit einem Fehlercode, damit er sich auch in der Automatik (CI) nutzen lässt.</p>

        <h2 id="dateien">5. Was die Befehle anlegen</h2>

        <h3>Blade-Komponente</h3>
        @verbatim
<pre><code>@props(['variant' => 'default', 'icon' => null])

@php
    $variants = ['default'];
    $variant = in_array($variant, $variants, true) ? $variant : 'default';
@endphp

&lt;div {{ $attributes->class(['vdbs-info-box', "vdbs-info-box--{$variant}"]) }}&gt;
    {{ $slot }}
&lt;/div&gt;</code></pre>
        @endverbatim
        <p>Die Komponente hat von Anfang an eine Variante (<code>variant</code>) mit sicherem Rückfall auf <code>default</code>. Neue Varianten tragen Sie in <code>$variants</code> ein und gestalten sie im CSS.</p>

        <h3>CSS</h3>
        @verbatim
<pre><code>.vdbs-info-box {
    padding: var(--space-4);
    background: var(--bg-surface);
    color: var(--text-default);
    border: 1px solid var(--border-default);
}

.vdbs-info-box--default { }</code></pre>
        @endverbatim
        <p>Das CSS beginnt mit einem lauffähigen Grundstil, der nur semantische Tokens nutzt. Sie ersetzen ihn durch Ihr Design.</p>

        <h3>Beispielseite</h3>
        <p>Die Beispielseite zeigt die Komponente im Standard und in jeder Variante. Ergänzen Sie dort Zustände, lange Texte, Größen und Sonderfälle. Der Dunkelmodus lässt sich in der Bibliothek über den Schalter „Darstellung wechseln“ im Fuß der Seite prüfen.</p>

        <h3>Manifest</h3>
        @verbatim
<pre><code>return [
    'title' => 'Meine Karten',
    'description' => 'Karten für Hinweise.',
    'components' => [
        [
            'name' => 'info-box',
            'title' => 'Info-Box',
            'description' => 'Eine Box mit Hinweis.',
            'status' => 'experimental',
            'width' => 'default',
        ],
    ],
];</code></pre>
        @endverbatim
        <p>Das Manifest speist die Design-Bibliothek. Titel, Beschreibung und Status dürfen Sie von Hand ändern. Mögliche Status: <code>experimental</code> (Standard), <code>beta</code>, <code>stable</code> und <code>deprecated</code>.</p>

        <h3>Test</h3>
        <p>Je Komponente entstehen zwei Tests in <code>tests/Feature/DesignLibrary/Packages/&lt;Paket&gt;Test.php</code>: Die Komponente wird mit Inhalt gerendert und muss ihre Klasse tragen, und eine unbekannte Variante fällt auf <code>default</code> zurück. Ergänzen Sie eigene Tests für Varianten, Eigenschaften und Barrierefreiheit.</p>

        <h2 id="arbeiten">6. So schreiben Sie eine Komponente</h2>
        <ol>
            <li><strong>Anlegen:</strong> <code>design:component</code> ausführen (am besten zuerst mit <code>--dry-run</code>).</li>
            <li><strong>Markup:</strong> In der Blade-Datei den Aufbau schreiben. Teile erhalten Klassen nach dem Muster <code>vdbs-info-box__teil</code>, Varianten <code>vdbs-info-box--variante</code>.</li>
            <li><strong>Eigenschaften:</strong> Alles, was sich ändert, wird eine Eigenschaft in <code>@@props</code>. Die Vorgabe soll ohne Angabe sinnvoll aussehen. Unbekannte Werte fallen auf die Vorgabe zurück (so wie bei <code>variant</code>).</li>
            <li><strong>CSS:</strong> Gestalten Sie mit Tokens: Abstände <code>--space-*</code>, Farben <code>--bg-*</code>, <code>--text-*</code>, <code>--border-*</code>, Schrift <code>--font-size-*</code>. Die Liste steht in <code>resources/css/vdbs/tokens.css</code> und in der Design-Bibliothek unter „Farben“, „Typografie“ und „Spacing“.</li>
            <li><strong>Zustände:</strong> Fokus (<code>:focus-visible</code>), Hover, deaktiviert und Fehler gestalten. Der Fokus muss immer sichtbar sein.</li>
            <li><strong>Beispielseite:</strong> Alle Varianten und Zustände zeigen, auch mit sehr langem Text und auf schmalem Bildschirm.</li>
            <li><strong>Dunkelmodus:</strong> Weil nur Tokens verwendet werden, passt er sich an. Prüfen Sie ihn trotzdem einmal.</li>
            <li><strong>Prüfen:</strong> <code>design:check</code> und die Tests ausführen (Abschnitt 8).</li>
            <li><strong>Status setzen:</strong> Im Manifest von <code>experimental</code> auf <code>beta</code> oder <code>stable</code> anheben, wenn die Komponente fertig ist.</li>
        </ol>

        <h2 id="regeln">7. Regeln für Code, CSS und Texte</h2>
        <table>
            <thead><tr><th scope="col">Bereich</th><th scope="col">Regel</th></tr></thead>
            <tbody>
                <tr><td>Farben</td><td>Keine Farbcodes (<code>#fff</code>, <code>rgb()</code>, <code>hsl()</code>) außerhalb von <code>tokens.css</code>. Komponenten nutzen nur semantische Tokens, nicht die Primitiven (<code>--color-*</code>, <code>--alpha-*</code>). Der Test <code>TokensTest</code> erzwingt das.</td></tr>
                <tr><td>Namen</td><td>Kleinbuchstaben mit Bindestrich. CSS-Klassen beginnen mit <code>vdbs-</code> und folgen BEM: <code>vdbs-name</code>, <code>vdbs-name__teil</code>, <code>vdbs-name--variante</code>.</td></tr>
                <tr><td>Ecken und Abstände</td><td>Ecken sind eckig, nur Pills und Avatare sind rund. Abstände kommen aus dem 4-Pixel-Raster (<code>--space-*</code>).</td></tr>
                <tr><td>Barrierefreiheit</td><td>Aussagekräftige Elemente (<code>button</code>, <code>nav</code>, <code>ul</code>) statt nur <code>div</code>. Jede Information steht auch als Text da, nie nur als Farbe. Bilder bekommen einen Alternativtext. Sichtbarer Fokus. Kontrast mindestens AA.</td></tr>
                <tr><td>Reaktion</td><td>Die Komponente muss ab 320 Pixel Breite ohne waagerechtes Scrollen funktionieren.</td></tr>
                <tr><td>Texte</td><td>Deutsch, in der Oberfläche mit „Sie“ (öffentliche Seiten für Schülerinnen und Schüler mit „du“). Texte gehören in den Slot oder in Eigenschaften, nicht fest in die Komponente.</td></tr>
                <tr><td>JavaScript</td><td>Nur, wenn es nötig ist, in <code>resources/js</code>. Ohne JavaScript muss der Inhalt erreichbar bleiben.</td></tr>
                <tr><td>Icons</td><td>Über <code>&lt;x-vdbs.icon name="..." /&gt;</code> mit den Lucide-Icons aus <code>resources/icons/lucide</code>.</td></tr>
            </tbody>
        </table>

        <h2 id="pruefen">8. Prüfen und testen</h2>
        @verbatim
<pre><code>php artisan design:check
php -d display_errors=1 vendor\pestphp\pest\bin\pest tests/Feature/DesignLibrary
php -d display_errors=1 vendor\pestphp\pest\bin\pest</code></pre>
        @endverbatim
        <ul>
            <li><strong>design:check</strong> findet fehlende Dateien und Verstöße gegen die Token-Regeln, bevor ein Test läuft.</li>
            <li><strong>tests/Feature/DesignLibrary</strong> enthält <code>TokensTest</code>, den Test <code>DesignLibraryTest</code> (jede Beispielseite muss ohne Fehler rendern) und die Paket-Tests.</li>
            <li>Der <strong>gesamte Testlauf</strong> sichert, dass sich nichts anderes verändert hat.</li>
        </ul>
        <p>Checkliste vor dem Zusammenführen: Beispielseite zeigt alle Varianten · Tastaturbedienung geprüft · Dunkelmodus geprüft · Handy-Breite geprüft · <code>design:check</code> ohne Meldung · Tests grün · Status im Manifest gesetzt.</p>

        <h2 id="fehler">9. Fehlerbehebung</h2>
        <table>
            <thead><tr><th scope="col">Meldung oder Symptom</th><th scope="col">Ursache und Lösung</th></tr></thead>
            <tbody>
                <tr><td>„Das Paket … gibt es nicht“</td><td>Zuerst <code>design:package &lt;name&gt;</code> ausführen oder den Namen prüfen.</td></tr>
                <tr><td>„Die Datei gibt es schon“</td><td>Die Komponente existiert. Mit <code>--force</code> überschreiben (Ihre Änderungen gehen verloren) oder einen anderen Namen wählen.</td></tr>
                <tr><td>„In resources/css/app.css fehlt der Anker“</td><td>Die Markierung <code>design-packages:imports</code> wurde entfernt. Wieder einfügen oder die <code>@@import</code>-Zeile von Hand ergänzen.</td></tr>
                <tr><td>Die Beispielseite zeigt keinen Stil</td><td><code>npm run build</code> (oder <code>npm run dev</code>) ausführen und die Seite hart neu laden.</td></tr>
                <tr><td>Beispielseite fehlt in der Bibliothek</td><td>Prüfen, ob der Eintrag im Manifest steht. Danach <code>php artisan view:clear</code>. Den Zugriff haben nur Konten mit <code>design.view</code>.</td></tr>
                <tr><td><code>TokensTest</code> schlägt fehl</td><td>Ein Farbcode oder ein primitives Token (<code>--color-*</code>) steckt im CSS. Durch ein semantisches Token ersetzen, das es in <code>tokens.css</code> gibt.</td></tr>
                <tr><td>„Vorlage fehlt“</td><td>Der Ordner <code>stubs/design</code> ist unvollständig. Aus dem Repository wiederherstellen.</td></tr>
            </tbody>
        </table>

        <h2 id="ausblick">10. Ausblick</h2>
        <p>Als Nächstes folgt der Befehl <code>design:docs &lt;paket&gt; &lt;name&gt;</code>, der zu jeder Komponente eine Erklärseite anlegt: Eigenschaften, Varianten, Barrierefreiheit und Einsatzhinweise. Er arbeitet nach demselben Muster wie die vorhandenen Befehle.</p>
    </div>
</x-layouts.app>
