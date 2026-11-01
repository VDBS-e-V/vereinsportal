# Design-Pakete (Werkzeug)

Die ausführliche Anleitung steht öffentlich im Portal unter `/design-pakete` (View `resources/views/portal/design-packages.blade.php`, Route `portal.design-packages`).

Kurz: `php artisan design:package <name>` legt ein Paket an, `php artisan design:component <paket> <name>` eine Komponente mit Blade, CSS, Beispielseite, Manifest-Eintrag und Test, `php artisan design:check [paket]` prüft alles. Die Logik steckt in `App\Modules\DesignLibrary\Support\DesignScaffolder` (getestet in `tests/Feature/DesignLibrary/DesignScaffolderTest.php`), die Vorlagen in `stubs/design/`, die Manifeste in `app/Modules/DesignLibrary/Packages/`. Die `Registry` der Design-Bibliothek liest die Manifeste ein.

Geplant: `design:docs <paket> <name>` für Erklärseiten je Komponente.
