# VDBS Designsystem v2 — Grundlagen

**Status:** Entwurf zur Abstimmung (Branch `design-system-v2/grundlagen`)
**Quelle der Wahrheit:** Corporate-Design-Markenpaket v7.2 (Farbpalette, Schriften, Formensprache)
**Parallelprojekt:** WordPress-Theme „VDBS e.V." (`theme.json`) — dieselben Marken-Tokens

Dieses Dokument beschreibt, was die Überarbeitung des Designsystems in diesem
ersten Schritt ändert, was bewusst **nicht** geändert wurde und welche
Entscheidungen noch offen sind. Es ergänzt `docs/08_DESIGN_SYSTEM_UND_WEB_UI.md`
und `docs/22_DESIGN_SYSTEM_V1_FREEZE.md`.

---

## 1. Ziel

Portal und öffentliche Website (WordPress) sollen **dieselbe Marke** zeigen. Das
v1-System war bereits nah am Markenpaket (Ink, Grautöne, Radius 0), wich aber bei
Schrift, Grün, Lila-Rolle, Link- und Fokusfarbe ab. Außerdem fehlten Rollen, die
das Markenpaket kennt (helle Markenfläche, Bühne, Gruppenfarben, Dunkelmodus).

## 2. Prinzipien

1. **Token-Namen bleiben.** Es werden Werte angepasst und Rollen ergänzt, keine
   zweite Token-Welt aufgebaut (Freeze-Regel aus `docs/22`). Alle Komponenten und
   Tests laufen unverändert weiter.
2. **Eine Quelle.** Hex-Werte stehen ausschließlich in `resources/css/vdbs/tokens.css`.
   Die Workbench-Seite `design/grundlagen` zeigt dieselben Werte.
3. **Eckig.** `--radius-default: 0` bleibt; rund sind nur Avatare (`--radius-avatar`).
4. **Dunkelmodus nur manuell,** nie automatisch über `prefers-color-scheme`
   (Vorgabe des Markenpakets).
5. **Kontrast zuerst.** Neue Farbkombinationen werden auf WCAG AA geprüft, bevor
   sie in Komponenten wandern.

## 3. Was in diesem Schritt geändert wurde

### 3.1 Farben (Werte)

| Token | v1 | v2 | Hinweis |
|---|---|---|---|
| `--color-primary` | `#2fbf71` | `#2dc08e` | Markengrün |
| `--color-secondary-accent` | `#582759` | `#58275a` | Markenlila, jetzt gleichrangig mit Grün |
| `--color-link` | `#086f64` | `#057f5b` | Link-Grün des Markenpakets |
| `--color-focus` | `--color-ink` | `--color-secondary-accent` | Fokusring in Lila |
| `--color-border-strong` | `#8d9a95` | `#7f8d88` | „Grau Rahmen“, Eingabefelder |
| `--color-danger` | `#a12622` | `#b42318` | „Fehler“ des Markenpakets |

Unverändert, weil bereits identisch mit dem Markenpaket: `--color-ink`,
`--color-surface-subtle`, `--color-border`, `--color-text-muted`.

### 3.2 Neue Rollen

| Token | Wert | Zweck |
|---|---|---|
| `--color-brand-soft` | `#dcf8ea` | helle Markenfläche (Karten, Hinweise) |
| `--color-stage` | `= --color-secondary-accent` | dunkle Markenfläche (Hero, Footer, Aufruf) |
| `--color-on-stage` | `#ffffff` | Text auf Bühne |
| `--color-stage-highlight` | `= --color-primary` | Akzent auf Bühne |
| `--color-accent-text` | `= --color-secondary-accent` | Lila als Text-/Icon-Farbe |
| `--color-on-accent` | `#ffffff` | Text auf Lila |
| `--color-link-visited` | `= --color-secondary-accent` | besuchte Links |
| `--color-group-1` … `-6` | Hellblau, Gelb, Petrol, Rosa, Rot, Orange | feste Kategorienpalette (Gruppen) |

### 3.3 Schrift

- **Basis:** Lato (400, 400 kursiv, 700, 700 kursiv, 900) statt IBM Plex Sans,
  selbst gehostet unter `public/fonts/lato/` (SIL OFL, Lizenztext liegt bei).
- **Überschriften:** H1/H2 in Lato Black (`--font-weight-black: 900`), H3–H6 bleiben 700.
- **Akzent:** Source Serif 4 (unverändert, für Zitate und Leitsätze).
- **Display:** Neuland (unverändert, nur für Hero/Seitentitel, nicht für UI).
- Die IBM-Plex-Dateien liegen noch in `public/fonts/ibm-plex-sans/`, werden aber
  nicht mehr referiert. Löschung: siehe offene Punkte.

### 3.4 Workbench

`/design/grundlagen` zeigt die neue Palette (inkl. Gruppenfarben) und markiert
Ocean Mist und Amber als Altbestand.

### 3.5 Durchgesetzt im Portal (Schritt 2)

| Stelle | Änderung |
|---|---|
| Ocean Mist (`--color-secondary-cta`) | entfernt; `btn--accent`, `badge--accent`, Hinweisbalken und Metadaten nutzen Lila (`--color-secondary-accent`), Text auf `btn--accent` weiß (Entscheidung B) |
| Zusatzfarben Amber, Teal, Berry, Sun Yellow, Lime | entfernt (waren nirgends verwendet); Kategorien laufen über `--color-group-1…6` |
| Hauptbutton und Login-Button | Hover in Lila statt Ink (Markenregel) |
| Footer-Leiste unten | Bühne (Lila) statt Ink; Fokusring dort weiß (Entscheidung F, lokal über `--color-focus`) |
| Besuchte Links in Artikeltexten | `--color-link-visited` (Lila) |
| Feste Hex-Werte | in `code-examples.css` und `frames.css` auf Tokens bzw. neue Werte umgestellt; Tailwind-`@theme` an die Tokens angeglichen |
| Docs | `docs/08` (Farben, Schrift, Button-Varianten) aktualisiert |

Bewusst unverändert: `print.css` (Schwarz/Weiß/Grau für den Druck), Pagination-Auswahl (Ink).

## 4. Was bewusst NICHT geändert wurde

- **Schriftgrößen-Skala** (`--text-*`). Das Markenpaket kennt 13/15/17/18/21/24 px
  und fluide Überschriften (H2 26–32, H1 40–56, Hero 40–64). Das Portal nutzt 16 px
  Basis für dichte Verwaltungsoberflächen. → offene Entscheidung A.
- **Abstandsskala.** `--spacing-1…10` (4–96 px) entspricht bereits der Skala des
  Themes (xxs…huge); nur die Namen unterscheiden sich. Mapping siehe 5.
- **Komponenten-CSS.** Kein einziger Komponentenselektor wurde angefasst.
- **Dunkelmodus.** Die Werte sind unten dokumentiert, aber nicht aktiv. → offene Entscheidung C.
- **`frozen`-Flag und Version** in `config/web_content_library.php`.
  → offene Entscheidung D.

## 5. Mapping zum WordPress-Theme

| Portal (CSS) | Theme (`theme.json` / Rollen) | Wert hell |
|---|---|---|
| `--color-ink` | `vdbs--text`, Preset `ink` | `#0F172A` |
| `--color-primary` | `vdbs--brand`, Preset `gruen` | `#2DC08E` |
| `--color-secondary-accent` | `vdbs--accent`, `stageDark`, Preset `lila` | `#58275A` |
| `--color-brand-soft` | `vdbs--accentSoft`, Preset `gruen-hell` | `#DCF8EA` |
| `--color-link` | `vdbs--link`, Preset `gruen-dunkel` | `#057F5B` |
| `--color-surface-subtle` | `vdbs--surface`, Preset `grau-hell` | `#F5F7F6` |
| `--color-border` | `vdbs--border`, Preset `grau-linie` | `#D4DBD8` |
| `--color-border-strong` | `vdbs--borderInput`, Preset `grau-rahmen` | `#7F8D88` |
| `--color-text-muted` | `vdbs--textMuted`, Preset `grau-text` | `#52605B` |
| `--color-danger` | `vdbs--error`, Preset `fehler` | `#B42318` |
| `--spacing-1…10` | `xxs, xs, s, m, l, xl, xxl, xxxl, huge` (4–96 px; `--spacing-7` 40 px hat kein Gegenstück) | — |

## 6. Dunkelmodus (dokumentiert, noch nicht aktiv)

Aktivierung ausschließlich per Attribut `data-vdbs-theme="dark"` am `<html>`
(Umschalter manuell, Wahl in `localStorage`). Werte laut Markenpaket:

| Rolle | hell | dunkel |
|---|---|---|
| Text | `#0F172A` | `#F1F4F2` |
| Text gedämpft | `#52605B` | `#A49CA3` |
| Hintergrund | `#FFFFFF` | `#180F18` |
| Fläche | `#F5F7F6` | `#261926` |
| Linie | `#D4DBD8` | `#3D303E` |
| Eingabe-Rahmen | `#7F8D88` | `#A49CA3` |
| Helle Markenfläche | `#DCF8EA` | `#261926` (Akzentfläche `#381D39`, Text `#EDBFEE`) |
| Bühne | `#58275A` | `#3B203B` |
| Link | `#057F5B` | `#40CD9A` |
| Link besucht / Akzenttext / Fokus | `#58275A` | `#E0A7E1` |
| Fehler | `#B42318` | `#FF8A80` |

Unverändert in beiden Modi: Markengrün, Lila als Fläche, Buttonfarben, Weiß auf Lila.

**Voraussetzung für die Aktivierung:** Komponenten nutzen heute `--color-ink` als
Textfarbe **und** als Fläche und `--color-white` als Fläche. Dafür braucht es
Rollen-Tokens (`--color-text`, `--color-bg`, …) und eine Migration der rund 130
`--color-ink`-Verwendungen.

## 7. Offene Entscheidungen

| # | Frage | Vorschlag |
|---|---|---|
| A | **Typografie-Skala:** Portal bei 16 px Basis lassen oder auf die 18-px-Skala des Markenpakets ziehen? | Zwei Skalen: öffentliche/redaktionelle Seiten 18 px (Markenpaket), Verwaltungsoberflächen 16 px. Beide aus denselben Tokens. |
| B | ~~**Ocean Mist** hat im Markenpaket keine Entsprechung.~~ | **Erledigt:** auf Lila migriert (siehe 3.5). |
| C | **Dunkelmodus** im Portal? | Ja, aber erst nach Rollen-Tokens (Abschnitt 6), mit Umschalter im Header. |
| D | **Freeze aufheben?** `docs/22` friert v1 ein. Eine Überarbeitung braucht eine bewusste Freigabe. | `config/web_content_library.php`: Version auf `2.0.0-dev`, `frozen: false` bis zum Abschluss, danach neu einfrieren. |
| E | **Altlasten:** IBM-Plex-Dateien, Zusatzfarben (Amber, Teal, Berry, Sun Yellow, Lime) | Löschen, sobald nichts mehr referenziert (`vdbs:library-check`). |
| F | **Fokusring Lila:** auf lila/dunklen Flächen ist Lila unsichtbar. | **Teilweise erledigt:** Footer-Leiste setzt `--color-focus` lokal auf Weiß. Weitere dunkle Flächen beim Einführen prüfen. |
| G | **Gemeinsame Token-Quelle** für Portal und Theme (z. B. eine `tokens.json`, aus der CSS und `theme.json` erzeugt werden)? | Später; erst wenn A–D entschieden sind. |

## 8. Vorgehen (Vorschlag)

1. **Grundlagen** (dieser Schritt): Werte, Rollen, Schrift, Dokument.
2. Entscheidungen A–F treffen.
3. Rollen-Tokens einführen (`--color-text`, `--color-bg`, `--color-surface` …) und Komponenten schrittweise darauf migrieren; Dunkelmodus freischalten.
4. Komponenten an den Webauftritt angleichen (Buttons, Karten, Hinweise, Header/Footer, Teaser), jeweils mit Eintrag in der Web Content Library.
5. Visuelle QA nach `docs/26_DESIGN_QA_EXECUTION.md` (Desktop/Mobil, hell/dunkel, Kontrast, Fokus, Print).
6. `docs/22` auf v2 aktualisieren und erneut einfrieren.

## 9. Komponenten-Werkstatt (CSS von Hand)

Für die Gestaltung der neuen Komponenten gibt es eine reine Struktur-Seite und
CSS-Dateien mit einer **Startgestaltung** (Entwurf, nur Tokens, zum Weiterbearbeiten):

| Was | Wo |
|---|---|
| Struktur-Seite (HTML ohne Styling) | `/design/komponenten` → `resources/views/design/pages/komponenten.blade.php` |
| Route | `routes/design/pages/komponenten.php` (Name `design.komponenten`) |
| CSS-Stubs (nur Selektoren) | `resources/css/vdbs/v2/*.css`, eingebunden über `v2/index.css` in `app.css` |
| Platzhalterbild | `public/images/design/platzhalter.svg` |

Die CSS-Dateien liegen bewusst **nicht** unter `css/vdbs/components/`, damit
`vdbs:library-check` sie nicht als unregistrierte Kern-Dateien meldet. Jede Datei
beginnt je Komponente mit einem Strukturbaum (Elemente und Klassen); darunter stehen
die Regeln der Komponente mit Varianten und Breakpoints.
Neu in `tokens.css`: `--text-display` (fluide Display-Schrift für Hero und Page Hero).

| Datei | Komponenten |
|---|---|
| `shared.css` | Aktions-Position, Bereich, Etikett, Meta-Zeile, `is-center` |
| `hero.css` | Hero „Bühne“, Banner, Page Hero, Intro-Teaser (3 Ausprägungen) |
| `teaser.css` | Teaser mit Bild (links, rechts, hervorgehoben), Bild-Text-Versatz, Bild-Text (Bild links/rechts) |
| `kacheln.css` | Kachelraster, Schwerpunkte, Karten-Reihe, Kennzahlen, Facts, Zahlenleiste, News |
| `inhalt.css` | Zitate, Textblock, Textfläche, Aufruf, Hinweisbox, Bildpaar, Textabschnitte |
| `personen.css` | Infospalten, Ansprechpartner:innen, Vorstand |
| `termine.css` | Terminliste, Veranstaltungssuche |
| `angebot.css` | Angebot-Bereich mit Modulen (`details`) |
| `formular.css` | Kontaktseite und Kontaktformular |

**Namen:** BEM (`vdbs-block__element--variante`), deutsch, identisch mit den Mustern des
WordPress-Themes. Zwei Namen weichen ab, weil das Portal sie schon belegt:
`vdbs-teaser` → `vdbs-bildteaser`, `vdbs-form` → `vdbs-formular`; `vdbs-badge`
(Plätze-Hinweis) heißt hier `vdbs-plaetze`. Im Theme müssten sie bei einer
gemeinsamen CSS-Quelle entsprechend umbenannt werden.

**Nicht enthalten:** Header, Footer und Brotkrumen (das Portal hat eigene Teile unter
`resources/css/vdbs/parts/`).

## 10. Prüfstand dieses Schritts

- `npm run build`: erfolgreich.
- Statische Prüfung der Pest-Tests auf geänderte Werte: keine Test-Assertion
  referenziert die angepassten Hex-Werte oder die Schrift.
- PHP-Tests und `vdbs:library-check` wurden in der Entwicklungsumgebung nicht
  ausgeführt (dort nur PHP 8.3, das Projekt verlangt ≥ 8.4.1) und müssen in CI
  bzw. lokal laufen.
