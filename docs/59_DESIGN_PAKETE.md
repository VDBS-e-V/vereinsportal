# Design-Pakete (Werkzeug)

Die ausführliche Anleitung steht öffentlich im Portal unter `/design-pakete` (View `resources/views/portal/design-packages.blade.php`, Route `portal.design-packages`).

Kurz: `php artisan design:package <name>` legt ein Paket an, `php artisan design:component <paket> <name>` eine Komponente mit Blade, CSS, Beispielseite, Manifest-Eintrag und Test, `php artisan design:check [paket]` prüft alles. Die Logik steckt in `App\Modules\DesignLibrary\Support\DesignScaffolder` (getestet in `tests/Feature/DesignLibrary/DesignScaffolderTest.php`), die Vorlagen in `stubs/design/`, die Manifeste in `app/Modules/DesignLibrary/Packages/`. Die `Registry` der Design-Bibliothek liest die Manifeste ein.

Die vorhandenen zwölf Pakete sind mit `design:adopt` in die Struktur aufgenommen (Manifeste in `app/Modules/DesignLibrary/Packages/`, Felder `adopted`, `redesign`, `blade`, `css`, `entries`). Die Übersicht mit dem Stand der Umgestaltung steht unter `/design/pakete`; `design:status` zeigt und setzt ihn.

Geplant: `design:docs <paket> <name>` für Erklärseiten je Komponente.
