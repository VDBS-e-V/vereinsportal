# Aktueller Arbeitsstand

## Branch

checkpoint/design-system-2026-09-08

## Aktuelles Thema

VDBS Portal Designsystem · Abschluss der Grundlagen und Migration der Identity-Oberfläche

## Implementiert

- vollständige Brand- und UI-Tokens
- IBM Plex Sans, Source Serif 4 und Neuland
- Portal Header, Footer und Breadcrumbs
- horizontale Navigation mit Desktop-Dropdowns und Mobile-Menü
- getrennte Designbereiche für Elemente, Muster und Vorlagen
- Buttons, Formulare, Hinweise, Status, Tabellen, Navigation, Suche und Filter
- Loading, Validierung, Dialoge, Dateien, Medien und Danger Zone
- Artikel, News, Veranstaltungen, Kontakte, Ressourcen, Key Facts und Datensatzlisten
- zentrale Icon-Bibliothek
- Print-Layer
- Accessibility-Grundlagen für Reduced Motion, High Contrast und Forced Colors
- vollständige Seitenvorlagen für Verwaltung, Redaktion, Termine und Fehlerseiten
- Identity-Seiten auf explizite Designsystem-Klassen migriert
- Konto-Navigation und Portal-Startseite als echte Produktoberfläche

## Architekturentscheidung

Produktseiten verwenden explizite Designsystem-Klassen und wiederverwendbare
Blade-Komponenten. Breite Fallback-Selektoren für beliebige Inputs oder Buttons
werden nicht mehr als Migrationsstrategie benötigt.

CSS definiert das visuelle System. Blade-Komponenten standardisieren
wiederkehrendes oder komplexes Markup. Fachseiten setzen beides zusammen.

## Qualität

Verbindliche automatische Checks:

```cmd
npm run build
php artisan test tests\Feature\Design
php artisan test tests\Feature\Identity
git diff --check
```

Die manuelle Abnahme folgt `docs/09_DESIGN_QA_CHECKLIST.md`.

## Als Nächstes

- manuelle Responsive-Abnahme bei 320, 375, 768, 1024 und 1280+ px
- Tastatur- und Screenreader-Prüfung
- Chrome, Firefox, Edge und Safari prüfen
- Print-Ansichten realer Seiten prüfen
- neue Fachmodule nur noch auf Basis der bestehenden Muster und Vorlagen umsetzen

Der Designsystem-Baukasten selbst soll nur noch erweitert werden, wenn ein
echter Fachfall ein bislang fehlendes wiederverwendbares Muster nachweist.
