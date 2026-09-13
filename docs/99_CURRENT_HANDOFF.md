# Aktueller Arbeitsstand

## Basis

`main`

## Aktuelles Thema

**Funktionale Beta-Abnahme vor Hosting/Staging**

Die übergreifende Reihenfolge steht in `docs/23_DEVELOPMENT_ROADMAP.md`. Die konkrete manuelle Abnahme steht in `docs/24_BETA_ACCEPTANCE.md`.

## Implementiert

Technische Basis:

- CI stabilisiert
- Larastan/PHPStan als `Static Analysis`
- PHP 8.4.1 und 8.5 in CI
- Security-Checks für Composer, npm, Dependency Review und Secrets
- aktives `Protect main`-Ruleset mit PR-Pflicht, linearer Historie, Conversation Resolution, Squash-only und Required Checks
- kein Ruleset-Bypass

Designsystem:

- Brand- und UI-Tokens
- Portal Header, Footer und Breadcrumbs
- responsive Navigation
- Buttons, Formulare, Hinweise, Status, Tabellen, Suche/Filter, Dialoge, Dateien und zentrale Icons
- Print-Layer
- Accessibility-Grundlagen für Reduced Motion, High Contrast und Forced Colors
- wiederverwendbare Seitenvorlagen und Web Content Library

Funktionale Beta:

- Personenverwaltung mit Suche, Pagination, Detail, Anlegen/Bearbeiten und Audit
- Personenliste mit Status, Name, E-Mail, Ort, letzter Anmeldung und Schnellaktionen
- Mitgliedschafts-Lebenszyklus mit Historie und automatischer `member`-Rollensynchronisation
- Mitgliedschaftsdokumente und Zustimmungsnachweise
- Audit-Liste/-detail mit Filtern und Datenminimierung
- Portal-Einladungen mit sicherem Lifecycle, Annahme und Konto-Verknüpfung
- Kommunikationsverwaltung mit Templates, Versionen und Deliveries
- capability-basierte Berechtigungen
- klare Trennung von Verwaltung, Vorstand und Koordination
- Systemrolle `member` nicht manuell administrierbar
- Mitgliedschafts- und zugehörige Audit-Daten ausschließlich für berechtigten Vorstand sichtbar

## Automatisierter Qualitätsstand

Referenz beim Wechsel in die Beta-Abnahme:

`959b7810a978078d0fbd9097919e0fc096a48cb5`

Auf diesem `main`-Stand waren erfolgreich:

- `Quality`
- `Static Analysis`
- PHP compatibility 8.4.1
- PHP compatibility 8.5
- `Composer Audit`
- `NPM Audit`
- `Dependency Review`
- `Secret Scan`

Die automatisierten Checks ersetzen keine manuelle Produkt-, Browser- oder Accessibility-Abnahme.

## Noch offen vor Beta-Freigabe

### Repository / GitHub

- #13: `Protect main` auf strict status checks umstellen; aktuell ist `strict_required_status_checks_policy = false`
- #14: Actions-Einstellungen in der GitHub-Oberfläche manuell gegen Zielwerte prüfen
- #15: Security-and-quality-Einstellungen in der GitHub-Oberfläche manuell gegen Zielwerte prüfen

### Designsystem / Accessibility

Tracking: #16

- Responsive 320 / 375 / 768 / 1024 / 1280+
- Tastatur- und Fokusprüfung
- Screenreader-Stichprobe
- Forced Colors / High Contrast
- Reduced Motion / erhöhter Kontrast
- Chrome / Firefox / Edge / Safari
- Print-Stichprobe
- lange Namen, E-Mail-Adressen, URLs und Dateinamen

### Funktionale Beta

Tracking: #22 und `docs/24_BETA_ACCEPTANCE.md`

- End-to-End: Person → Mitgliedschaft → Einladung → Konto → Login/2FA
- Rollen-/Berechtigungsgrenzen manuell als unterschiedliche Rollen prüfen
- E-Mail-/Delivery-Nachvollziehbarkeit prüfen
- Audit-Nachvollziehbarkeit prüfen
- Fehler- und Leerzustände stichprobenartig prüfen
- Abnahme mit Datum, Umgebung und Ergebnis dokumentieren

## Nächster Gate-Wechsel

Erst wenn #13, #14, #15, #16 und das Beta-Abnahme-Gate aus #22 abgeschlossen sind:

1. Designsystem v1 als freigegeben/frozen dokumentieren.
2. #16 und #22 schließen.
3. #19 zur aktiven Priorität machen.
4. Hosting-/Betriebsarchitektur konkretisieren.
5. Danach Staging, Release-Probelauf und Produktion vorbereiten.

## Grundsatz

Neue große Fachmodule werden bis zur Beta-Abnahme nicht parallel auf Vorrat begonnen. Gefundene Beta-/QA-Fehler werden als gezielte Patches behoben. Reine Dokumentations- und Betriebsplanung darf parallel weiterlaufen.
