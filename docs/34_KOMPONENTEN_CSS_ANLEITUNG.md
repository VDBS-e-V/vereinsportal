# Anleitung: Komponenten von Hand mit CSS gestalten

Diese Anleitung beschreibt, wie die v2-Komponenten unter `/design/komponenten`
gestaltet werden. Hintergrund und Entscheidungen: `docs/33_DESIGN_SYSTEM_V2_GRUNDLAGEN.md`.

**Das Prinzip:** Das HTML steht fest (Blade-Seite), die Klassen sind als leere Regeln
angelegt. Es wird ausschließlich CSS geschrieben, mit den Tokens aus `tokens.css`.

---

## 1. Einmalige Einrichtung

### 1.1 Repository und Branch

```text
git clone https://github.com/VDBS-e-V/vereinsportal
cd vereinsportal
git fetch origin design-system-v2/grundlagen
git switch design-system-v2/grundlagen
```

Für eigene Arbeit besser einen Unterbranch anlegen (Namensschema aus `CONTRIBUTING.md`,
hier z. B. `feature/v2-css-kacheln`):

```text
git switch -c feature/v2-css-kacheln
```

### 1.2 Projekt starten

Voraussetzungen: PHP 8.4.1+, Composer, Node.js, npm, lokale Datenbank.

```text
composer setup        # Abhängigkeiten, .env, App-Key, Migrationen, npm ci, Build
composer dev          # Server, Queue, Logs und Vite (Hot Reload) in einem Terminal
```

### 1.3 Domain `my.vdb.test`

Die Design-Seiten sind an die Portal-Domain gebunden (`VDB_MY_DOMAIN` in `.env`,
Standard `my.vdb.test`). Die Domain muss lokal auf `127.0.0.1` zeigen
(Windows: `C:\Windows\System32\drivers\etc\hosts`, Eintrag `127.0.0.1 my.vdb.test`).
Der Entwicklungsserver läuft dann unter `http://my.vdb.test:8000`, sofern `php artisan serve`
verwendet wird. Die Design-Routen sind in `local` und `testing` immer aktiv.

### 1.4 Anmelden

Die Design-Seiten verlangen eine Anmeldung (`auth`, `identity.revalidate`). Für lokale
Konten:

```text
php artisan vdbs:test-accounts create
```

Zugangsdaten und TOTP-Secrets liegen danach in `storage/app/private/test-accounts.json`
(nicht committen, siehe `docs/32_LOCAL_TEST_ACCOUNTS.md`).

### 1.5 Seite öffnen

`http://my.vdb.test:8000/design/komponenten`. Die Seite hat oben ein Inhaltsverzeichnis,
jeder Abschnitt ist per Anker erreichbar (z. B. `#kacheln`). Beispiele sind gestrichelt
umrandet.

---

## 2. Wo liegt was?

| Zweck | Datei |
|---|---|
| HTML-Struktur (nicht anfassen, außer Struktur ändert sich) | `resources/views/design/pages/komponenten.blade.php` |
| Deine CSS-Dateien | `resources/css/vdbs/v2/*.css` |
| Einbindung | `resources/css/vdbs/v2/index.css` (von `app.css` importiert) |
| Tokens (Farben, Abstände, Schrift …) | `resources/css/vdbs/tokens.css` |
| Globale Grundstile (Überschriften, Links, Bilder) | `resources/css/vdbs/base.css` |
| Breiten-Container | `resources/css/vdbs/layout.css` |
| Vorhandene Buttons | `resources/css/vdbs/components/buttons.css` |

| CSS-Datei | Komponenten |
|---|---|
| `shared.css` | Aktions-Position, Bereich, Etikett, Meta-Zeile, `is-center` |
| `hero.css` | Hero, Banner, Page Hero, Intro |
| `teaser.css` | Teaser mit Bild, Bild-Text-Versatz |
| `kacheln.css` | Kacheln, Schwerpunkte, Karten, Kennzahlen, Facts, Zahlenleiste, News |
| `inhalt.css` | Zitate, Textblock, Textfläche, Aufruf, Hinweisbox, Bildpaar, Textabschnitte |
| `personen.css` | Infospalten, Ansprechpartner, Vorstand |
| `termine.css` | Terminliste, Veranstaltungssuche |
| `angebot.css` | Angebot-Bereiche mit Modulen |
| `formular.css` | Kontaktseite, Formular |

---

## 3. Arbeitsablauf pro Komponente

1. **Abschnitt öffnen** (Anker) und die Komponente ansehen: Sie ist noch ungestaltet.
2. **CSS-Datei öffnen.** Jede Komponente beginnt mit einem Kommentar mit Beschreibung und
   **Strukturbaum**, z. B.:

   ```text
   section.vdbs-banner.vdbs-banner--buehne
     figure.vdbs-banner__bild
     div.vdbs-banner__inner
       div.vdbs-banner__karte
         h2.vdbs-banner__titel
   ```
   Eingerückt = Kind-Element. `×3` bedeutet: drei gleiche Geschwister.
3. **Zuerst Block und Layout:** Wurzel (`.vdbs-banner`) → Wrapper (`__inner`) → Elemente.
   Reihenfolge: Layout (display, grid/flex, Breiten, Abstände) → Fläche (Hintergrund,
   Rahmen) → Typografie → Zustände (`:hover`, `:focus-visible`) → Breakpoints.
4. **Varianten zuletzt** (`--rechts`, `--gruen`, `--hervorgehoben`). Sie überschreiben nur,
   was sich unterscheidet.
5. **Im Browser prüfen** (Abschnitt 6), speichern, nächste Komponente.
6. **Committen** (Abschnitt 8): eine Komponente oder Komponentengruppe pro Commit.

**Empfohlene Reihenfolge**, weil spätere Teile auf früheren aufbauen:
`shared.css` → `kacheln.css` (Kachel, Karte) → `teaser.css` → `hero.css` → `inhalt.css`
→ `personen.css` → `termine.css` → `angebot.css` → `formular.css`.

---

## 4. Regeln

### 4.1 Nur Tokens, keine festen Werte

Farben, Abstände und Schriftgrößen kommen aus `tokens.css`. Feste Werte (`#58275a`, `24px`)
gehören nicht in Komponenten.

| Zweck | Token |
|---|---|
| Text | `--color-ink`; gedämpft `--color-text-muted` |
| Fläche weiß / hell / stärker | `--color-white`, `--color-surface-subtle`, `--color-surface-strong` |
| Helle Markenfläche | `--color-brand-soft` (Text darauf: `--color-ink`) |
| Dunkle Markenfläche (Bühne) | `--color-stage`, Text `--color-on-stage`, Akzent `--color-stage-highlight` |
| Marke Grün / Lila | `--color-primary` / `--color-secondary-accent` (Textfarbe: `--color-accent-text`) |
| Gruppenfarben | `--color-group-1` … `-6` |
| Rahmen / stark | `--color-border` / `--color-border-strong` |
| Links / besucht | `--color-link` / `--color-link-visited` |
| Fokus | `--color-focus` |
| Status | `--color-success`, `-warning`, `-danger`, `-info` |
| Abstände | `--spacing-1` 4 px, `-2` 8, `-3` 12, `-4` 16, `-5` 24, `-6` 32, `-7` 40, `-8` 48, `-9` 64, `-10` 96 |
| Schrift | `--text-xs` 12 … `--text-2xl` 24, `--text-3xl`/`-4xl` fluid; Familie `--font-family-base` (Lato), `-accent` (Source Serif), `-workshop` (Neuland) |
| Gewicht | `--font-weight-regular/-bold/-black` |
| Zeilenhöhe | `--leading-tight`, `--leading-heading`, `--leading-body` |
| Breiten | `--layout-content-max` 80rem, `--layout-text-max` 46rem, `--layout-page-gutter` |
| Linien / Steuerelemente | `--border-width-default/-strong/-accent`, `--control-height-sm/-md/-lg` |
| Schatten / Ebenen / Bewegung | `--shadow-sm/-md/-lg`, `--z-*`, `--transition-fast/-normal` |

Was fehlt, wird **nicht** als Wert in die Komponente geschrieben, sondern als Token
vorgeschlagen (Abschnitt 9).

### 4.2 Formensprache

- `border-radius: 0` für alle Flächen, Karten, Buttons, Eingabefelder. Rund sind nur
  **Avatare** (`.vdbs-person__bild img`: `border-radius: var(--radius-avatar)`).
- Schatten nur für Ebenen (Header, Dropdown, Dialog), nicht für normale Karten.
- Keine Verläufe als Dekoration. Ausnahme: der dunkle Verlauf unter Text auf Fotos
  (`.vdbs-mitglied__text`), damit der Kontrast stimmt.

### 4.3 Kein `!important`, keine Tailwind-Utilities in diesen Dateien

- Spezifität über die Klassenstruktur steuern (`.vdbs-banner--buehne .vdbs-banner__karte`),
  nicht über `!important`.
- In den v2-Dateien reines CSS. Tailwind-Utilities bleiben für einmalige Layouts in
  Blade-Seiten.
- Buttons nicht hier neu stylen: Es gelten `btn` und `btn--outline`
  (`components/buttons.css`). Bedarf an neuen Button-Varianten → dort ändern, nicht
  pro Komponente.

### 4.4 Breakpoints

Das Projekt nutzt Desktop-first mit `max-width`. Verwende die vorhandenen Stufen:

| Breite | Verwendung |
|---|---|
| `max-width: 64rem` (1024 px) | 3 → 2 Spalten, Facts-Raster |
| `max-width: 48rem` (768 px) | Zweispaltiges → einspaltiges Layout, Teaser stapeln |
| `max-width: 40rem` (640 px) | Formular und Raster einspaltig |
| `max-width: 30rem` (480 px) | alles einspaltig |

### 4.5 Barrierefreiheit (Pflicht)

- Text-Kontrast mindestens 4,5 : 1 (großer Text 3 : 1). Auf `--color-stage` nur
  `--color-on-stage` verwenden.
- Jedes interaktive Element braucht ein sichtbares `:focus-visible` (global in
  `base.css`; nur anpassen, wenn Lila auf Lila unsichtbar wäre, z. B. auf `--color-stage`:
  `outline-color: var(--color-on-stage)`).
- Klickflächen mindestens 44 × 44 px (Buttons: `min-height: var(--control-height-md)`).
- Hover ist nie der einzige Hinweis. Bewegung nur über `--transition-*`, sie wird bei
  „reduzierte Bewegung“ automatisch zu 0.
- Reihenfolge im HTML ist die Lesereihenfolge. Visuelle Umstellung per `order` oder
  `grid-area` nur, wenn die Reihenfolge trotzdem sinnvoll bleibt.

---

## 5. Rezepte

Alle Beispiele sind Ausgangspunkte, keine Vorgabe. Namen passen zu den Stubs.

### 5.1 Vollbreites Band mit begrenztem Inhalt

Fläche auf die Wurzel, Breite auf `__inner`:

```css
.vdbs-aufruf {
    padding-block: var(--spacing-8);
    background: var(--color-stage);
    color: var(--color-on-stage);
    text-align: center;
}

.vdbs-aufruf__inner {
    width: min(calc(100% - 2 * var(--layout-page-gutter)), var(--layout-text-max));
    margin-inline: auto;
}
```

Gilt für alle Komponenten mit `__inner`: `pagehero`, `flaeche`, `aufruf`, `kacheln`,
`facts`, `zahlen`.

### 5.2 Raster, das auf kleinen Bildschirmen von selbst umbricht

```css
.vdbs-karten {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--spacing-8) var(--spacing-6);
}

@media (max-width: 64rem) {
    .vdbs-karten { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 30rem) {
    .vdbs-karten { grid-template-columns: minmax(0, 1fr); }
}
```

`minmax(0, 1fr)` verhindert, dass lange Wörter die Spalte aufdehnen.

### 5.3 Button halb über der Kante (`vdbs-aktion`)

Die Elternfläche (Kachel, Karte) ist `display: flex; flex-direction: column` und hat
unten `padding-bottom: var(--spacing-5)`. Die Aktion zieht sich um die Hälfte ihrer Höhe
**plus dieses Innenabstands** nach unten:

```css
.vdbs-aktion {
    --vdbs-aktion-h: 2.25rem;
    align-self: flex-end;
    margin-block-start: auto;
    margin-block-end: calc(-1 * (var(--spacing-5) + var(--vdbs-aktion-h) / 2));
    margin-inline-end: var(--spacing-3);
}

.vdbs-aktion .btn {
    min-height: var(--vdbs-aktion-h);
}

.vdbs-aktion--flach {
    margin: 0;
    align-self: flex-start;
}
```

Der Abstand nach unten (Zeilenabstand im Raster) muss mindestens die halbe Buttonhöhe
betragen, sonst berührt der Button die nächste Kachel.

### 5.4 Ganze Fläche klickbar (Stretched Link)

Die Fläche ist `position: relative`, der Button-Link bekommt ein unsichtbares Overlay:

```css
.vdbs-kachel,
.vdbs-karte,
.vdbs-news__eintrag,
.vdbs-termin { position: relative; }

.vdbs-kachel .btn::before,
.vdbs-karte .btn::before {
    content: "";
    position: absolute;
    inset: 0;
}
```

Bei `--flach` (kein Overlay) `content: none` setzen. Fokus der Fläche sichtbar machen mit
`.vdbs-kachel:has(.btn:focus-visible) { outline: 2px solid var(--color-focus); }`.

### 5.5 Bild ragt über die Fläche hinaus (Teaser, Karte, Kennzahl)

Karte per `::before` als Fläche hinter Bild und Text legen, Bild per negativem Rand
herausziehen:

```css
.vdbs-karte {
    position: relative;
    isolation: isolate;
    display: flex;
    flex-direction: column;
}

.vdbs-karte::before {
    content: "";
    position: absolute;
    inset: 0.625rem 0 0 0;    /* Fläche beginnt etwas unter der Bildkante */
    z-index: -1;
    background: var(--color-brand-soft);
}

.vdbs-karte__bild {
    position: relative;
    z-index: 1;
    width: 86%;
    margin: 0 0 0 -0.625rem; /* ragt links über die Fläche */
}

.vdbs-karte__bild img {
    width: 100%;
    aspect-ratio: 3 / 2;
    object-fit: cover;
}
```

`isolation: isolate` ist wichtig, sonst rutscht das `z-index: -1` hinter den Seitenhintergrund.
Beim **Teaser** liegen Bild und Inhalt im selben Grid-Bereich
(`grid-area: 1 / 1`): Bild links (48 %), Inhalt über die volle Breite mit
`padding-inline-start: calc(48% + var(--spacing-5))` und `margin-block-start` als Überstand.
Beim **Bild-Text-Versatz** genauso, nur mit der Karte über dem Bild
(`z-index: 1`, Breite ca. 43 %, `align-self: center`).

### 5.6 Wellenkante

```css
.vdbs-banner--welle::after {
    content: "";
    position: absolute;
    inset: auto 0 -1px 0;
    height: 1.25rem;
    background: var(--color-surface-subtle);
    mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 20' preserveAspectRatio='none'%3E%3Cpath d='M0 20V10a16 10 0 0 1 32 0 16 10 0 0 1 32 0v10z'/%3E%3C/svg%3E")
        bottom left / 4rem 100% repeat-x;
}
```

Die Farbe der Welle ist die Farbe der **folgenden** Fläche.

### 5.7 Foto als Hintergrund

`figure.vdbs-banner__bild` liegt als erstes Kind: absolut über die ganze Fläche, dahinter,
Bild füllt per `object-fit: cover`. Die Wurzel braucht `position: relative` und
`isolation: isolate`.

### 5.8 Text über Foto (Vorstand)

Overlay-Text unten mit Verlauf, damit der Kontrast auch auf hellen Bildern stimmt:

```css
.vdbs-mitglied__text {
    position: absolute;
    inset: auto 0 0 0;
    padding: var(--spacing-8) var(--spacing-4) var(--spacing-4);
    background: linear-gradient(to top, rgb(15 23 42 / 90%), rgb(15 23 42 / 0%));
    color: var(--color-white);
}
```

3 Karten pro Reihe, die letzten zentriert: `display: flex; flex-wrap: wrap;
justify-content: center;` und `flex: 0 1 calc((100% - 2 * var(--spacing-5)) / 3)`.

### 5.9 Trennlinien-Liste (Terminzeilen)

Zeile mit Linie unten, Button sitzt darauf (Rezept 5.3 mit `border-bottom`). Datumsspalte
links per Grid: `grid-template-columns: 8rem minmax(0, 1fr)`. Auf Mobil einspaltig, Datum
in einer Zeile.

### 5.10 Ausklappbare Module (`details`)

```css
.vdbs-modul { border: var(--border-width-default) solid var(--color-border); }
.vdbs-modul + .vdbs-modul { margin-block-start: var(--spacing-2); }
.vdbs-modul summary { padding: var(--spacing-3) var(--spacing-4); cursor: pointer; font-weight: var(--font-weight-bold); }
.vdbs-modul[open] { border-color: var(--color-ink); }
.vdbs-modul > :not(summary) { padding: 0 var(--spacing-4) var(--spacing-4); }
```

### 5.11 Akzentfarbe je Variante

Für `vdbs-angebot`: eine Variable pro Variante, Titel und Strich lesen sie aus:

```css
.vdbs-angebot { --vdbs-akzent: var(--color-link); }
.vdbs-angebot--petrol { --vdbs-akzent: var(--color-group-3); }
.vdbs-angebot--rot { --vdbs-akzent: var(--color-group-5); }
.vdbs-angebot__titel { color: color-mix(in srgb, var(--vdbs-akzent) 75%, var(--color-ink)); }
```

(Die Mischung mit Ink hält den Kontrast; Gruppenfarben allein sind als Text teils zu hell.)

### 5.12 Formular

Zweispaltiges Grid (`vdbs-formular`), `__feld--voll` über beide Spalten. Eingabefelder:
Rahmen `--color-border-strong`, Innenabstand `--spacing-3 --spacing-4`, Fokus 3 px
`--color-focus`. Bereits vorhandene Formularstile in `components/forms.css`
(`.form__control`, Validierung) dienen als Vorlage für Zustände.

---

## 6. Prüfen

### 6.1 Im Browser (jede Komponente)

- **Breiten:** 1440, 1280, 1024, 768, 640, 375 px (DevTools Geräteleiste).
- **Zoom 200 %** und Schriftgröße „sehr groß“: nichts darf abgeschnitten oder
  überlappt sein.
- **Tastatur:** mit Tab durchgehen, Fokus immer sichtbar, Reihenfolge logisch.
- **Kontrast:** DevTools → Element → Farbwähler zeigt das Verhältnis; Ziel ≥ 4,5 : 1.
- **Lange Texte** (ein sehr langer Titel, ein langes Wort) und **kurze Texte**
  (leere Absätze) testen, einfach im Browser den Text ändern.
- **Reduzierte Bewegung:** DevTools → Rendering → „prefers-reduced-motion“.
- **Print** (Strg+P) bei Komponenten, die gedruckt werden sollen: Hintergründe fehlen im
  Druck, Text muss trotzdem lesbar sein.

### 6.2 Build und Checks

```text
npm run build         # muss ohne Fehler laufen
composer qa           # Pint, PHPStan, Tests, library-check, Build
```

`composer qa` ändert für reine CSS-Arbeit nichts an Pint/PHPStan, prüft aber, dass nichts
Bestehendes kaputtgeht.

---

## 7. Häufige Fallstricke

| Symptom | Ursache | Lösung |
|---|---|---|
| Änderung wirkt nicht | Vite läuft nicht oder Browser-Cache | `composer dev` (oder `npm run dev`) prüfen, Strg+F5 |
| Hintergrund fehlt hinter Karte | `z-index: -1` ohne `isolation: isolate` | Rezept 5.5 |
| Bild verzerrt | `height` gesetzt | nur `width: 100%; aspect-ratio; object-fit: cover` |
| Grid-Spalte dehnt sich | `1fr` statt `minmax(0, 1fr)` | `minmax(0, 1fr)` |
| Überschrift hat unerwarteten Abstand | globale Stile in `base.css` (`margin: 0`) und Stack-Klassen | Abstände über `gap` des Elternelements |
| Link ist unterstrichen/gefärbt | globale Link-Stile | in der Komponente `text-decoration: none; color: inherit` |
| Button sieht anders aus als im Theme | `btn` ist portal-eigen | Button-Varianten zentral in `buttons.css` anpassen |
| Wort bricht mitten im Wort | zu große Schrift in schmaler Spalte | `overflow-wrap: break-word; hyphens: auto` oder Schrift fluid per `clamp()` |
| Vollbreite Komponente ist nicht randlos | Design-Seite hat Container | Demo-Rahmen `vdbs-demo--full` sorgt dafür; in echten Seiten Komponente außerhalb des Containers einsetzen |
| Leere Regeln fehlen im Build | Build entfernt leere Regeln | normal, kein Fehler |

---

## 8. Committen

- Branch von `design-system-v2/grundlagen` bzw. `main` (nach Merge). Namensschema
  `feature/…` (siehe `CONTRIBUTING.md`).
- Kleine, thematische Commits, Conventional Commits:
  `feat(design): style tile grid and cards`, `fix(design): focus ring on stage surfaces`.
- Vor jedem Commit: `git diff --check` (keine Leerzeichen am Zeilenende), `npm run build`.
- Eintrag im `CHANGELOG.md` unter *Unreleased → Changed/Added*, wenn eine Komponente
  fertig ist.
- Pull Request nach `main` (Squash-Merge). Beschreibung nach `.github/pull_request_template.md`
  (Screenshots Desktop/Mobil beilegen).

---

## 9. Wenn etwas fehlt

- **Neuer Wert nötig** (Farbe, Abstand, Schriftgröße): nicht fest in die Komponente
  schreiben. Als Token in `tokens.css` ergänzen und in `docs/33` kurz begründen.
- **Struktur muss sich ändern** (zusätzliches Element, andere Reihenfolge): erst das HTML in
  `komponenten.blade.php` anpassen, dann die neue Klasse als leere Regel in die passende
  v2-Datei und in den Strukturbaum-Kommentar eintragen. Die Struktur im WordPress-Theme
  (`patterns/*.php`) muss dann gleich nachgezogen werden, damit beide gleich bleiben.
- **Neue Komponente:** Abschnitt in der Blade-Seite, Datei in `v2/` (oder bestehende Datei
  ergänzen), Eintrag in `v2/index.css`. Name nach `vdbs-block__element--variante`.
  Vorher prüfen, ob die Klasse schon existiert:
  `grep -rn "\.vdbs-meinname" resources/css`.

## 10. Wenn eine Komponente fertig ist

Fertige Komponenten wandern aus `v2/` in `resources/css/vdbs/components/` und werden
dabei in der Web Content Library registriert (`config/web_content_library.php`,
Ablauf in `docs/16_WEB_CONTENT_LIBRARY_ADDING_CONTENT.md`; `php artisan vdbs:library-check`
prüft die Inventur). Dann gilt sie als stabil, die Stubs in `v2/` entfallen.

## 11. Ins WordPress-Theme übernehmen (optional)

Das Theme nutzt dieselben Klassennamen mit drei Ausnahmen:

| Portal | Theme |
|---|---|
| `vdbs-bildteaser` | `vdbs-teaser` |
| `vdbs-formular` | `vdbs-form` |
| `vdbs-plaetze` | `vdbs-badge` |

Weitere Unterschiede: Im Theme sind Buttons `wp-block-button__link`, im Portal `btn`;
Wrapper-Namen für Vollbreite (`__inner`) gibt es dort nicht (WordPress begrenzt die Breite
über Layout-Einstellungen). Das CSS lässt sich deshalb nicht 1:1 kopieren, die Werte
(Abstände, Verhältnisse, Reihenfolge der Regeln) aber schon.
