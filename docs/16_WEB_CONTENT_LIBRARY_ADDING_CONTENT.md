# Web Content Bibliothek selbst erweitern

## Grundregel

Nicht mit einer neuen Komponente anfangen, sondern mit der Frage:

1. Gibt es bereits einen passenden Baustein?
2. Lässt sich der Anwendungsfall als Variante eines vorhandenen Bausteins lösen?
3. Ist es ein wiederverwendbarer UI-Baustein, ein zusammengesetztes Muster,
   ein redaktioneller Content-Baustein oder eine vollständige Vorlage?

Erst wenn vorhandene Elemente nicht ausreichen, wird etwas Neues angelegt.

## A. Bestehenden Baustein nur dokumentieren

Wenn Blade/CSS bereits existiert, reicht oft ein neuer oder verbesserter Eintrag in
`config/web_content_library.php`.

Pflichtfelder:

```php
[
    'id' => 'eindeutige-kebab-case-id',
    'name' => 'Lesbarer Name',
    'category' => 'elements',
    'status' => 'experimental',
    'description' => 'Wofür ist der Baustein da?',
    'source' => 'resources/...',
    'files' => [
        'resources/...',
    ],
    'tags' => [
        'suchwort',
    ],
    'usage' => [
        'Konkreter guter Anwendungsfall.',
    ],
    'avoid' => [
        'Konkreter falscher Anwendungsfall.',
    ],
    'accessibility' => [
        'Konkrete Barrierefreiheitsregel.',
    ],
],
```

Gültige Kategorien:

- `foundations`
- `elements`
- `patterns`
- `content`
- `templates`
- `tools`

Gültige Status:

- `experimental`
- `beta`
- `stable`
- `deprecated`

Neue Dinge beginnen normalerweise mit `experimental` oder `beta`.

## B. Neue VDBS-Komponente

Zuerst Dry-Run:

```bat
php artisan vdbs:make-component content-highlight --dry-run
```

Wenn die Dateiliste passt:

```bat
php artisan vdbs:make-component content-highlight
```

Der Generator erzeugt ein Gerüst für:

- Blade-Komponente
- Komponenten-CSS
- Designsystem-Seite
- Design-Test

Danach manuell:

1. Komponente fachlich ausarbeiten.
2. Neue CSS-Datei in `resources/css/app.css` importieren.
3. Design-Route ergänzen.
4. Registry-Eintrag ergänzen.
5. Vorschau und Codebeispiel ergänzen.
6. Accessibility-Regeln dokumentieren.
7. Tests laufen lassen.

Der Generator überschreibt absichtlich keine existierenden Dateien.

## C. Neues Muster

```bat
php artisan vdbs:make-pattern resource-teaser --dry-run
php artisan vdbs:make-pattern resource-teaser
```

Ein Muster muss nicht zwingend eine eigene Blade-Komponente besitzen. Wenn es nur eine
empfohlene Kombination vorhandener Komponenten ist, ist eine Designsystem-Dokumentation
oft die bessere Lösung.

## D. Neues Icon

Neue Icons nicht einzeln in die Library-Registry eintragen.

Stattdessen in:

`resources/views/components/vdbs/icon.blade.php`

einen neuen `@case('name')` ergänzen. Der Icon-Browser liest diese Cases automatisch aus.

Danach prüfen:

```bat
php artisan test tests\Feature\Design\IconBrowserTest.php
```

## E. Neue Button-Variante

Keine neue Button-Komponente anlegen.

1. Variante in `resources/css/vdbs/components/buttons.css` ergänzen.
2. Falls sie im Playground auswählbar sein soll:
   `app/Support/ButtonExampleBuilder.php` erweitern.
3. Einsatzgrenzen dokumentieren.
4. Design-Test ergänzen.

## F. Neue Designsystem-Seite

Routen liegen unter:

`routes/design/pages/`

Views liegen unter:

`resources/views/design/pages/`

Eine Dokumentationsseite sollte mindestens enthalten:

- verständlicher Name
- Zweck
- echte Vorschau
- wann verwenden
- wann nicht verwenden
- Accessibility-Hinweise
- möglichst kopierbares Beispiel mit `<x-vdbs.code-example>`

## G. Abschlussprüfung

Immer ausführen:

```bat
php artisan vdbs:library-check
php artisan test tests\Feature\Design
npm run build
git diff --check
git status --short
```

`vdbs:library-check` prüft nicht nur die Registry-Felder und Dateipfade.
Das Kommando entdeckt außerdem neue VDBS-Blade-Komponenten und Komponenten-CSS-Dateien,
die noch nicht inventarisiert wurden.

## Wann ist ein Eintrag stabil?

`stable` erst setzen, wenn:

- der echte Anwendungsfall existiert,
- die API/Props verständlich sind,
- Responsive-Verhalten geprüft ist,
- Tastatur/Fokus geprüft ist,
- Kontrast und Semantik passen,
- eine Designsystem-Dokumentation existiert,
- Tests vorhanden sind.

Bis dahin `experimental` oder `beta` verwenden.
