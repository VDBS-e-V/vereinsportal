# Aktueller Arbeitsstand

## Basis

`main`

## Aktuelles Thema

VDBS Portal Designsystem · manuelle v1-QA und dokumentierter Abschluss

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
- CI stabilisiert und statische PHP-Analyse mit Larastan/PHPStan integriert
- unterstützte PHP-Basis auf 8.4.1+ vereinheitlicht und in CI geprüft

## Architekturentscheidung

Produktseiten verwenden explizite Designsystem-Klassen und wiederverwendbare
Blade-Komponenten. Breite Fallback-Selektoren für beliebige Inputs oder Buttons
werden nicht mehr als Migrationsstrategie benötigt.

CSS definiert das visuelle System. Blade-Komponenten standardisieren
wiederkehrendes oder komplexes Markup. Fachseiten setzen beides zusammen.

## Qualität

Die automatisierte Basis auf `main` ist grün. Für den Designsystem-Abschluss
bleiben die Prüfungen aus `docs/22_DESIGN_SYSTEM_V1_FREEZE.md` verbindlich:

```cmd
php artisan vdbs:library-check
php artisan vdbs:design-status
php artisan test tests\Feature\Design
npm run build
git diff --check
git status --short
```

Zusätzlich laufen im Repository `Quality`, `Static Analysis`, die unterstützte
PHP-Matrix und die Security-Checks über GitHub Actions.

Die manuelle Abnahme folgt `docs/09_DESIGN_QA_CHECKLIST.md` und ist noch nicht
abgeschlossen.

## Als Nächstes

- manuelle Responsive-Abnahme bei 320, 375, 768, 1024 und 1280+ px
- vollständige Tastaturprüfung einschließlich Fokusführung und Escape-Verhalten
- Screenreader-Stichprobe für Navigation, Formulare, Hinweise und Dialoge
- Forced Colors / High Contrast, Reduced Motion und erhöhten Kontrast prüfen
- Chrome, Firefox, Edge und Safari anhand der Browser-Matrix prüfen
- Print-Ansichten realer Seiten prüfen
- lange Namen, E-Mail-Adressen, URLs und Dateinamen prüfen
- gefundene QA-Fehler als gezielte Patches beheben
- erst danach Designsystem v1 gemäß `docs/22_DESIGN_SYSTEM_V1_FREEZE.md` freigeben

Neue Fachmodule sollen weiterhin nur auf Basis der bestehenden Muster und
Vorlagen umgesetzt werden. Der Designsystem-Baukasten wird nur erweitert, wenn
ein echter Fachfall ein bislang fehlendes wiederverwendbares Muster nachweist.
