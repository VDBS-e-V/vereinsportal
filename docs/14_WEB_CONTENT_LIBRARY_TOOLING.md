# Web Content Library — Entwicklungswerkzeuge

## Komponenten erzeugen

```bat
php artisan vdbs:make-component content-highlight --dry-run
php artisan vdbs:make-component content-highlight
```

Der Generator erzeugt:

- Blade-Komponente
- Komponenten-CSS
- Designsystem-Seite
- Design-Test

Er überschreibt keine vorhandenen Dateien. CSS-Import, Route und Registry-Eintrag
werden bewusst nicht automatisch verändert, damit diese Änderungen geprüft
und fachlich eingeordnet werden.

## Muster erzeugen

```bat
php artisan vdbs:make-pattern resource-teaser --dry-run
php artisan vdbs:make-pattern resource-teaser
```

Der Generator erzeugt Dokumentation und Design-Test für ein neues Muster.

## Bibliothek prüfen

```bat
php artisan vdbs:library-check
```

Die Prüfung schlägt fehl bei:

- doppelten IDs
- unbekannten Kategorien
- unbekannten Status
- fehlenden Quellpfaden

Das Kommando ist für lokale Qualitätssicherung und später auch für CI gedacht.
