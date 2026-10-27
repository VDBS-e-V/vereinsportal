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
- Zusätzlich bietet jede Fehlerseite mehrere weiterführende Links („Das könnte
  Ihnen helfen“), damit niemand in einer Sackgasse landet (siehe „Weiterführende
  Links“).
- Fehlermeldungen enthalten keine Stacktraces, internen IDs oder technischen
  Details für Endnutzer:innen.
- Sicherheitsrelevante Zustände wie 403 und 419 bleiben sachlich.
- 500 und 503 versprechen keine konkrete Wiederherstellungszeit.
- Die Seiten zeigen den normalen Portal-Header und -Footer. Sie bleiben aber
  auch ohne die vollständige Portalnavigation verständlich und dürfen nie selbst
  fehlschlagen (siehe „Header und Footer“).

## Varianten

### 401 — Anmeldung erforderlich

Für Seiten, die eine Anmeldung voraussetzen, wenn der Zugriff nicht über die
normale Weiterleitung zur Anmeldung läuft (z. B. `abort(401)`).
Rückweg: Anmeldung; optional Startseite.

### 403 — Zugriff nicht erlaubt

Für existierende Inhalte, auf die das aktuelle Konto nicht zugreifen darf.
Rückweg: Startseite; optional Anmeldung mit einem anderen Konto.

### 404 — Seite nicht gefunden

Für nicht vorhandene oder verschobene Inhalte.
Rückweg: Startseite.

### 419 — Sitzung abgelaufen

Für abgelaufene CSRF-/Sitzungskontexte.
Rückweg: erneute Anmeldung.

### 429 — Zu viele Anfragen

Für Drosselung (Rate Limiting), z. B. bei Anmeldeversuchen.
Rückweg: später erneut versuchen; optional Startseite.

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
der linken Fläche („Bild links, Text rechts“). Die Illustration zeigt den Fehlercode
selbst, deshalb entfallen große Zahl und Signal; die technische Zeile „Fehlercode …“
bleibt erhalten. Ohne
`image-alt` gilt das Bild als dekorativ (`aria-hidden`), mit `image-alt="…"` wird es
vorgelesen. Die Variante wird pro Fehlerseite in `resources/views/errors/*.blade.php`
über die Eigenschaft eingeschaltet. Beispiel:
`/design/fehlerseiten`. Styles: Abschnitt „Variante mit Bild“ in
`resources/css/vdbs/components/error-pages.css`.

### Bilder der Fehlerseiten

Ablage: `public/images/fehlerseiten/<code>.webp` (Verhältnis 3:2, z. B. 1536 × 1024).

| Datei | Fehlerseite | Stand |
|---|---|---|
| `404.webp` | 404 Seite nicht gefunden | im Repo |
| `500.webp` | 500 Technischer Fehler | im Repo |
| `401.webp` | 401 Anmeldung erforderlich | im Repo |
| `403.webp` | 403 Zugriff nicht erlaubt | im Repo |
| `429.webp` | 429 Zu viele Anfragen | im Repo |
| `wartungsarbeiten.webp` | 503 Vorübergehend nicht verfügbar | im Repo |

419 hat bisher kein Bild. Die Bilder werden im Format 3:2 gezeigt (Bildfläche mit festem
Seitenverhältnis, kein Zuschnitt). Dateien bitte als WebP in 1536 × 1024 und möglichst unter
250 KB ablegen; Fehlerseiten müssen schnell laden.

## Weiterführende Links

Jede Fehlerseite zeigt unter den Buttons die Liste „Das könnte Ihnen helfen“ mit bis zu
vier Links. Die Links liefert `app/Support/ErrorPageLinks.php`; die Views übergeben sie
über `:links="\App\Support\ErrorPageLinks::for('404')"`.

| Link | Wann |
|---|---|
| Zur Startseite | immer |
| Zurück zur vorherigen Seite | nur Status < 500, nur wenn die vorherige Seite auf demselben Host liegt |
| Anmelden, Konto registrieren, Passwort vergessen | für Gäste |
| Mein Konto, Mein Profil | für angemeldete Personen (nur Status < 500) |

Der Baustein entfernt Links, deren Ziel schon als Button vorkommt (Vergleich über den
Pfad), und zeigt jeden Text nur einmal. Ab Status 500 gibt es weder Sitzungs- noch
Anmeldeabfrage, also nur Gast-Links. Eigene Links: Eigenschaft `links` mit einer Liste
aus `['label' => …, 'url' => …]`, Überschrift über `links-title`.

## Layout des Bausteins

Aufbau von oben nach unten: helle Fläche (`--color-brand-soft`) aus **Text links** und
**Bild bzw. Signalfläche rechts**, darunter die Linkkacheln „Das könnte Ihnen helfen“.

- Das Bild steht mit Abstand in der Fläche und hat immer das Format 3:2.
- Das Eyebrow („Fehler“) und sein Quadrat tragen die Farbe der Variante (`info` Lila,
  `warning`, `danger`, `success`).
- Mobil (bis 48 rem): Bild, Text mit vollbreiten Buttons, Linkkacheln untereinander.
- Die kompakte Fassung (`compact`) ist für Übersichten gedacht und bleibt zweispaltig.
- Das CSS steht in `resources/css/vdbs/components/error-pages.css`. Die `vdbs-`-Selektoren
  sind Kopien der kurzen Namen und werden immer zusammen geändert.

