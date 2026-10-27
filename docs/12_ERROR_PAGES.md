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
- Die Seiten zeigen den normalen Portal-Header und -Footer. Sie bleiben aber
  auch ohne die vollständige Portalnavigation verständlich und dürfen nie selbst
  fehlschlagen (siehe „Header und Footer“).

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
  ist die Hülle der Fehlerseiten. Sie bindet den normalen Portal-Header und
  -Footer ein (über `App\Support\ErrorPageChrome`) und fällt bei Problemen auf
  die reduzierte Hülle zurück.
- `app/Support/ErrorPageChrome.php`
  baut Header und Footer abgesichert (siehe unten).
- `resources/views/errors/partials/fallback-header.blade.php` und
  `fallback-footer.blade.php`
  sind die reduzierte Kopf- und Fußzeile für den Notfall.
- `resources/views/errors/{403,404,419,500,503}.blade.php`
  sind die Laravel-Runtime-Fehlerseiten.
- `resources/css/vdbs/components/error-pages.css`
  enthält Layout, Varianten, Responsive- und Print-Regeln.
- `/design/fehlerseiten`
  dokumentiert das Muster im Designsystem.

Die Runtime-Fehlerseiten verwenden bewusst nicht den vollständigen interaktiven
Portal-Header. Dadurch bleibt die Fehlerdarstellung auch dann robust, wenn ein
Fehler innerhalb der Portalnavigation oder einer Fachseite entsteht.

## Header und Footer

Die Fehlerseiten verwenden `x-vdbs.portal-header` und `x-vdbs.portal-footer`
wie das normale Portal-Layout. Weil eine Fehlerseite auch erscheint, wenn
Datenbank oder Sitzung ausgefallen sind, baut `ErrorPageChrome` sie abgesichert:

| Fall | Header |
|---|---|
| Status < 500 (403, 404, 419) | mit angemeldeter Person (Bereiche, Konto-Menü), wenn Sitzung und Datenbank antworten |
| Status ≥ 500 (500, 503, Wartungsmodus) | immer Gast-Header, ohne Sitzung, Anmeldung und Datenbank |
| Aufbau schlägt fehl | reduzierte Kopf- und Fußzeile (`errors/partials/fallback-*`) |

Der Header der Fehlerseiten ist bewusst einfacher als der des normalen Layouts:
Navigation (Start, Konto bzw. Zugang), Brotkrumen und Konto-Menü (Profil,
Einstellungen, Abmelden). Änderungen an der Hauptnavigation des Portals müssen hier
nur nachgezogen werden, wenn sie auch auf Fehlerseiten erscheinen sollen.

## Variante mit Bild

`<x-vdbs.error-page … :image="asset('images/…')" />` zeigt statt des Signals ein Bild in
der linken Fläche („Bild links, Text rechts“). Der Fehlercode bleibt als Marke unten
links sichtbar, die technische Zeile „Fehlercode …“ bleibt erhalten. Ohne
`image-alt` gilt das Bild als dekorativ (`aria-hidden`), mit `image-alt="…"` wird es
vorgelesen. Die Variante ist standardmäßig nicht aktiv; sie wird pro Fehlerseite in
`resources/views/errors/*.blade.php` über die Eigenschaft eingeschaltet. Beispiel:
`/design/fehlerseiten`. Styles: Abschnitt „Variante mit Bild“ in
`resources/css/vdbs/components/error-pages.css`.
