# VDBS Fehlerseiten

## Ziel

Fehlerseiten sind Teil des VDBS-Designsystems und keine technischen
Standardseiten. Sie sollen einen Fehler verständlich erklären, die Marke
erkennbar halten und einen sicheren Rückweg anbieten.

## Grundprinzipien

- Der Fehlercode bleibt sichtbar, wird aber immer in Alltagssprache erklärt.
- Pro Fehlerseite gibt es genau eine primäre Aktion.
- Eine zweite Aktion ist nur sinnvoll, wenn sie einen echten alternativen
  Rückweg anbietet.
- Fehlermeldungen enthalten keine Stacktraces, internen IDs oder technischen
  Details für Endnutzer:innen.
- Sicherheitsrelevante Zustände wie 403 und 419 bleiben sachlich.
- 500 und 503 versprechen keine konkrete Wiederherstellungszeit.
- Die Seiten bleiben auch ohne die vollständige Portalnavigation verständlich.

## Varianten

### 403 — Zugriff nicht erlaubt

Für existierende Inhalte, auf die das aktuelle Konto nicht zugreifen darf.
Rückweg: Startseite; optional Anmeldung mit einem anderen Konto.

### 404 — Seite nicht gefunden

Für nicht vorhandene oder verschobene Inhalte.
Rückweg: Startseite.

### 419 — Sitzung abgelaufen

Für abgelaufene CSRF-/Sitzungskontexte.
Rückweg: erneute Anmeldung.

### 500 — Technischer Fehler

Für unerwartete interne Fehler.
Rückweg: Startseite; optional erneuter Versuch.

### 503 — Vorübergehend nicht verfügbar

Für Wartung oder temporär nicht verfügbare Dienste.
Rückweg: erneuter Versuch oder Startseite.

## Technische Struktur

- `resources/views/components/vdbs/error-page.blade.php`
  enthält das wiederverwendbare Fehlermuster.
- `resources/views/errors/layout.blade.php`
  stellt eine bewusst robuste, reduzierte VDBS-Hülle bereit.
- `resources/views/errors/{403,404,419,500,503}.blade.php`
  sind die Laravel-Runtime-Fehlerseiten.
- `resources/css/vdbs/components/error-pages.css`
  enthält Layout, Varianten, Responsive- und Print-Regeln.
- `/design/fehlerseiten`
  dokumentiert das Muster im Designsystem.

Die Runtime-Fehlerseiten verwenden bewusst nicht den vollständigen interaktiven
Portal-Header. Dadurch bleibt die Fehlerdarstellung auch dann robust, wenn ein
Fehler innerhalb der Portalnavigation oder einer Fachseite entsteht.
