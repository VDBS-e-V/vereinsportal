# VDBS Web-Designsystem und Portal-UI

**Projekt:** VDBS Vereinsportal
**Technik:** Laravel 13, Livewire/Volt, Tailwind CSS 4, Vite
**Lokaler Designbereich:** `http://my.vdb.test:8000/design`
**Status:** Gestaltungsgrundlage / Portal-Shell

---

## 1. Zweck dieses Dokuments

Dieses Dokument beschreibt die technische und gestalterische Struktur der Weboberfläche des VDBS Vereinsportals.

Die UI orientiert sich strukturell am früheren VDBS-Portal und wird für das neue Laravel-Projekt vereinfacht, vereinheitlicht und barrierearm weitergeführt. Als gestalterische Referenzen dienen institutionelle Webportale mit klarer Informationsarchitektur, insbesondere kommunale und universitäre Auftritte.

Das wichtigste Prinzip lautet:

> Fachseiten enthalten Fachinhalt. Wiederverwendbare Gestaltung wird zentral im Designsystem definiert.

Dadurch soll eine neue Seite nicht bei null beginnen. Entwicklerinnen und Entwickler sollen auf vorhandene Tokens, Layouts, Komponenten, Headerstrukturen, Formulare, Tabellen, Navigationsmuster und Designreferenzen zurückgreifen können.

---

## 2. Gestaltungsrichtung

### Wirkung

Das Portal soll sachlich, freundlich, lebendig, modern, vertrauenswürdig und übersichtlich wirken. Es soll nicht experimentell sein und ausdrücklich nicht wie ein typisches SaaS-Dashboard oder eine generische KI-Webseite aussehen.

Vermeiden:

- dekorative Farbverläufe als Standard,
- Glows,
- riesige Marketing-Heros ohne fachlichen Zweck,
- überall schwebende Karten,
- übermäßig runde Oberflächen,
- beliebige Pill-Badges,
- unnötige Animationen,
- wechselnde Designsprachen zwischen öffentlichen und geschützten Bereichen.

### Grundfläche

Die Hauptfarben des Webinterfaces sind White und Ink. VDBS Green ist die primäre Akzentfarbe. Ocean Mist und die übrigen Markenfarben werden gezielt eingesetzt.

### Formensprache

Normale UI-Flächen sind eckig:

```css
border-radius: 0;
```

Das gilt für Buttons, Inputs, Selects, Textareas, Cards, Panels, Dropdowns, Hinweise, Tabellencontainer und Navigationsflächen.

Bewusste Ausnahme: Benutzeravatare dürfen rund sein.

### Schatten

Schatten sind erlaubt und zeigen Ebene oder Überlagerung an. Typische Einsatzbereiche sind Header, Sticky-Zustand, Dropdown, Account-Menü, Modal und Popover. Normale Inhalte benötigen nicht automatisch einen Schatten.

---

## 3. Markenfarben

Die zentralen Brand-Tokens lauten:

```css
--color-ink: #0f172a;
--color-white: #ffffff;
--color-primary: #2fbf71;
--color-secondary-cta: #22b7a3;
--color-accent-amber: #f59e0b;
--color-accent-teal: #14b8a6;
--color-accent-berry: #d946ef;
```

| Token | Name | Verwendung |
|---|---|---|
| `--color-ink` | Ink | Text, Navigation, starke Linien |
| `--color-white` | White | Hauptfläche |
| `--color-primary` | VDBS Green | Marke, aktive Zustände, Hauptaktionen |
| `--color-secondary-cta` | Ocean Mist | zweite Akzent- / Aktionsfarbe |
| `--color-accent-amber` | Amber | gezielter Kontext |
| `--color-accent-teal` | Teal | gezielter Kontext |
| `--color-accent-berry` | Berry | gezielter Kontext |

Keine Fachseite soll eine Markenfarbe neu definieren. Verwende Tokens.

---

## 4. Typografie

### Standardschrift

`--font-family-base` ist für IBM Plex Sans vorgesehen und wird für Navigation, Fließtext, Überschriften, Formulare, Tabellen, Buttons und Verwaltungsoberflächen verwendet.

### Akzentschrift

`--font-family-accent` ist Source Serif 4. Sie darf für Zitate, Leitsätze, besondere redaktionelle Aussagen und kurze bewusst hervorgehobene Texte verwendet werden.

```html
<p class="editorial-accent">
    Weil Schule uns alle angeht!
</p>
```

### Workshop-Schrift

`--font-family-workshop` ist für Neuland vorgesehen. Sie ist keine allgemeine Portal-Schrift, sondern nur für kurze methodische Begriffe.

---

## 5. Verzeichnisstruktur des Designsystems

Das Designsystem ist bewusst über mehrere Verzeichnisse verteilt. Jedes Verzeichnis hat eine klar abgegrenzte Aufgabe. Die wichtigste Regel lautet:

> **Gestaltung, Verhalten, Routing und Dokumentation werden getrennt gehalten.**

Dadurch bleibt nachvollziehbar, **wo** eine Änderung vorgenommen werden muss und **welche Art von Verantwortung** eine Datei besitzt.

### 5.1 Gesamtübersicht

```text
vereinsportal/
├── app/
│   └── Console/
│       └── Commands/
│           └── MakeDesignPageCommand.php
│
├── bootstrap/
│   └── app.php
│
├── config/
│   └── design.php
│
├── docs/
│   └── 08_DESIGN_SYSTEM_UND_WEB_UI.md
│
├── public/
│   └── images/
│       └── brand/
│           └── vdbs-logo.png
│
├── resources/
│   ├── css/
│   │   ├── app.css
│   │   └── vdbs/
│   │       ├── tokens.css
│   │       ├── base.css
│   │       ├── layout.css
│   │       ├── design.css
│   │       ├── parts/
│   │       │   ├── header.css
│   │       │   └── footer.css
│   │       └── components/
│   │           ├── buttons.css
│   │           ├── cards.css
│   │           ├── forms.css
│   │           ├── grids.css
│   │           ├── links.css
│   │           ├── notices.css
│   │           └── tables.css
│   │
│   ├── js/
│   │   ├── app.js
│   │   └── portal-header.js
│   │
│   └── views/
│       ├── components/
│       │   └── vdbs/
│       │       ├── icon.blade.php
│       │       ├── portal-footer.blade.php
│       │       └── portal-header.blade.php
│       │
│       ├── design/
│       │   ├── layout.blade.php
│       │   ├── index.blade.php
│       │   └── pages/
│       │       ├── elemente.blade.php
│       │       ├── grundlagen.blade.php
│       │       ├── header.blade.php
│       │       └── vorlagen.blade.php
│       │
│       ├── livewire/
│       │   └── identity/
│       │       └── ...
│       │
│       └── components/
│           └── layouts/
│               └── public.blade.php
│
├── routes/
│   ├── web.php
│   ├── design.php
│   └── design/
│       └── pages/
│           ├── elemente.php
│           ├── grundlagen.php
│           ├── header.php
│           └── vorlagen.php
│
└── tests/
    └── Feature/
        └── Design/
            ├── DesignSystemPageTest.php
            ├── MakeDesignPageCommandTest.php
            ├── PortalHeaderDesignPageTest.php
            └── PortalLayoutDesignTest.php
```

Die Verzeichnisstruktur bildet gleichzeitig die Architektur des Designsystems ab:

```text
Konfiguration
    ↓
Design-Tokens
    ↓
globale Basis
    ↓
Layout
    ↓
Header / Footer
    ↓
UI-Komponenten
    ↓
Blade-Komponenten
    ↓
Design-Referenzseiten
    ↓
echte Fachseiten
```

---

### 5.2 `resources/css/`

Hier liegt die komplette visuelle Grundlage.

```text
resources/css/
├── app.css
└── vdbs/
```

#### `resources/css/app.css`

`app.css` ist der zentrale CSS-Einstiegspunkt für Vite.

Diese Datei soll möglichst **keine große Menge an eigentlichen Komponentenregeln** enthalten. Ihre Hauptaufgabe ist:

- Tailwind laden,
- VDBS-CSS-Dateien importieren,
- Tailwind-Quellen bekannt machen,
- gegebenenfalls Tailwind-Brand-Tokens registrieren.

Beispielprinzip:

```css
@import 'tailwindcss';

@import './vdbs/tokens.css';
@import './vdbs/base.css';
@import './vdbs/layout.css';

@import './vdbs/parts/header.css';
@import './vdbs/parts/footer.css';

@import './vdbs/components/buttons.css';
@import './vdbs/components/forms.css';
@import './vdbs/components/grids.css';
@import './vdbs/components/links.css';
@import './vdbs/components/cards.css';
@import './vdbs/components/notices.css';
@import './vdbs/components/tables.css';

@import './vdbs/design.css';
```

**Merksatz:**  
Wenn du eine Button-Regel ändern möchtest, bearbeitest du nicht `app.css`, sondern `components/buttons.css`.

---

### 5.3 `resources/css/vdbs/tokens.css`

Diese Datei enthält die **Grundwerte des Designs**.

Dazu gehören insbesondere:

- Farben,
- Schriftfamilien,
- Schriftgewichte,
- Spacing,
- maximale Seitenbreiten,
- Seitenränder,
- Schatten,
- Übergänge,
- Formwerte.

Beispiele:

```css
--color-primary
--color-ink
--spacing-5
--layout-content-max
--shadow-lg
```

Wenn du eine Änderung machst, die **das gesamte Portal betreffen soll**, ist `tokens.css` häufig der richtige Ort.

---

### 5.4 `resources/css/vdbs/base.css`

`base.css` definiert die globale HTML-Grundlage:

- `html`,
- `body`,
- Box-Sizing,
- Grundtypografie,
- globale Überschriften,
- Standardlinks,
- Fokuszustände,
- `::selection`,
- grundlegendes Verhalten von `button`, `input`, `select`, `textarea`.

Hier gehören nur Regeln hinein, die praktisch überall gelten.

---

### 5.5 `resources/css/vdbs/layout.css`

`layout.css` enthält die strukturellen Seitenbausteine.

Typische Klassen:

```css
.site-main
.container
.container--wide
.container--narrow
.container--form
.stack
.stack--sm
.stack--lg
.cluster
.section
.page-title
.page-title__kicker
.page-title__title
.page-title__lead
.page-title--split
```

Hier wird also definiert:

> **Wie wird Inhalt auf einer Seite angeordnet?**

---

### 5.6 `resources/css/vdbs/parts/`

In `parts/` liegen große globale Seitenteile.

```text
resources/css/vdbs/parts/
├── header.css
└── footer.css
```

#### `header.css`

Enthält ausschließlich den globalen Portalheader:

- obere Bereichsnavigation,
- untere Seitennavigation,
- aktive Navigation,
- Untermenüs,
- Avatar,
- Benutzer-Popover,
- Mobile-Menü,
- Sticky-Zustände,
- Scrollzustände.

#### `footer.css`

Enthält ausschließlich den globalen Footer.

---

### 5.7 `resources/css/vdbs/components/`

Hier liegen die wiederverwendbaren UI-Komponentenfamilien.

```text
components/
├── buttons.css
├── cards.css
├── forms.css
├── grids.css
├── links.css
├── notices.css
└── tables.css
```

Eine Datei bündelt jeweils **eine Komponentenfamilie**.

Wenn eine neue Komponentenfamilie entsteht, kann z. B. ergänzt werden:

```text
resources/css/vdbs/components/pagination.css
```

Danach muss sie in `resources/css/app.css` importiert werden.

---

### 5.8 `resources/css/vdbs/design.css`

Diese Datei ist ausschließlich für den internen Designbereich bestimmt.

Typische Klassen:

```css
.design-example
.design-code
.design-swatch
```

Diese Klassen sind **keine Produktkomponenten** und sollen nicht auf echten Fachseiten verwendet werden.

---

### 5.9 `resources/js/`

```text
resources/js/
├── app.js
└── portal-header.js
```

#### `app.js`

Zentraler JavaScript-Einstiegspunkt.

#### `portal-header.js`

Enthält ausschließlich das Verhalten des Portalheaders:

- Scrollrichtung,
- obere Headerzeile ein-/ausblenden,
- Mobile-Menü,
- Account-Menü,
- Untermenüs,
- Escape-Verhalten,
- Klick außerhalb,
- ARIA-Zustände,
- Livewire-Reinitialisierung.

Neue größere Interaktionen erhalten eigene Dateien.

---

### 5.10 `resources/views/components/vdbs/`

Hier liegen wiederverwendbare Blade-Komponenten des Designsystems.

```text
resources/views/components/vdbs/
├── icon.blade.php
├── portal-footer.blade.php
└── portal-header.blade.php
```

Wichtig beim Header:

```text
HTML:
resources/views/components/vdbs/portal-header.blade.php

CSS:
resources/css/vdbs/parts/header.css

JavaScript:
resources/js/portal-header.js
```

Diese Trennung ist verbindlich.

---

### 5.11 `resources/views/design/`

Dies ist der interne Vorlagen- und Elemente-Bereich.

```text
resources/views/design/
├── layout.blade.php
├── index.blade.php
└── pages/
```

Der Designbereich besitzt bewusst **keine permanente linke Seitennavigation**. Er verwendet denselben horizontalen Portalheader wie die Anwendung.

Unter `pages/` liegt jede einzelne Referenzseite.

Beispiel:

```text
resources/views/design/pages/formulare/textfelder.blade.php
```

---

### 5.12 `resources/views/components/layouts/public.blade.php`

Diese Datei verbindet das Designsystem mit den bestehenden Laravel-/Livewire-Seiten.

Sie stellt den gemeinsamen Rahmen bereit:

```text
Portal Header
    ↓
Seiteninhalt
    ↓
Portal Footer
```

Dadurch bekommen bestehende Identity-Seiten zentrale Designänderungen automatisch mit.

---

### 5.13 `resources/views/livewire/`

Hier liegen die **Fachseiten**.

Dort gehören hinein:

- Formulare,
- Seitentexte,
- Livewire-Zustand,
- Benutzerinteraktion,
- fachliche UI.

Dort gehören **keine neuen globalen Designregeln** hinein.

Fachseiten konsumieren das Designsystem; sie definieren es nicht.

---

### 5.14 `routes/design.php`

Zentrale Routendatei des Designbereichs.

Sie definiert:

- `/design` als Prefix,
- `design.` als Route-Name-Prefix,
- Schutz des Bereichs,
- Laden der Einzelseiten aus `routes/design/pages/`.

---

### 5.15 `routes/design/pages/`

Zu jeder Designseite gehört eine Routendatei.

Beispiel:

```text
View:
resources/views/design/pages/formulare/textfelder.blade.php

Route:
routes/design/pages/formulare/textfelder.php

URL:
http://my.vdb.test:8000/design/formulare/textfelder

Route-Name:
design.formulare.textfelder
```

---

### 5.16 `app/Console/Commands/MakeDesignPageCommand.php`

Generator für neue Designseiten.

```cmd
php artisan make:design-page buttons --title="Buttons"
```

Er erstellt:

1. die Blade-Seite,
2. die Routendatei.

Dadurch bleibt die Struktur konsistent.

---

### 5.17 `config/design.php`

Konfiguration des internen Designbereichs.

Hier gehören keine visuellen Werte hinein.

Farben, Abstände und Schatten gehören nach:

```text
resources/css/vdbs/tokens.css
```

---

### 5.18 `bootstrap/app.php`

Hier wird das zusätzliche Designrouting in Laravel eingebunden.

Neue Designseiten benötigen **keine** Änderung an `bootstrap/app.php`.

---

### 5.19 `public/images/brand/`

Zentraler Ort für Markenassets.

Aktuell:

```text
public/images/brand/vdbs-logo.png
```

Die Anzeigegröße wird über CSS gesteuert.

---

### 5.20 `tests/Feature/Design/`

Eigene Tests für das Designsystem:

```text
tests/Feature/Design/
├── DesignSystemPageTest.php
├── MakeDesignPageCommandTest.php
├── PortalHeaderDesignPageTest.php
└── PortalLayoutDesignTest.php
```

---

### 5.21 `docs/08_DESIGN_SYSTEM_UND_WEB_UI.md`

Diese Datei ist die technische Referenz des Designsystems.

Sie soll im selben Entwicklungsblock aktualisiert werden, wenn sich Grundregeln ändern.

---

### 5.22 So findest du schnell die richtige Datei

| Ich möchte ... | Datei / Verzeichnis |
|---|---|
| eine Markenfarbe ändern | `resources/css/vdbs/tokens.css` |
| Spacing oder Seitenbreite ändern | `resources/css/vdbs/tokens.css` |
| globale Typografie ändern | `resources/css/vdbs/base.css` |
| Container oder Seitenlayout ändern | `resources/css/vdbs/layout.css` |
| Header-HTML ändern | `resources/views/components/vdbs/portal-header.blade.php` |
| Header-Styling ändern | `resources/css/vdbs/parts/header.css` |
| Header-Verhalten ändern | `resources/js/portal-header.js` |
| Footer-HTML ändern | `resources/views/components/vdbs/portal-footer.blade.php` |
| Footer-Styling ändern | `resources/css/vdbs/parts/footer.css` |
| Button ändern | `resources/css/vdbs/components/buttons.css` |
| Formular ändern | `resources/css/vdbs/components/forms.css` |
| Tabelle ändern | `resources/css/vdbs/components/tables.css` |
| Card oder Teaser ändern | `resources/css/vdbs/components/cards.css` |
| Hinweis ändern | `resources/css/vdbs/components/notices.css` |
| Breadcrumb / Linkmuster ändern | `resources/css/vdbs/components/links.css` |
| Designvorschau gestalten | `resources/css/vdbs/design.css` |
| Icon ergänzen | `resources/views/components/vdbs/icon.blade.php` |
| neue Designseite erstellen | `php artisan make:design-page ...` |
| Fachseiteninhalt ändern | `resources/views/livewire/...` |

---

### 5.23 Verantwortungsgrenzen

```text
tokens.css
    = Werte

base.css
    = globale HTML-Basis

layout.css
    = Seitenstruktur

parts/
    = globale Seitenteile

components/
    = wiederverwendbare UI-Elemente

components/vdbs/*.blade.php
    = wiederverwendbares HTML

design/
    = Referenz und Dokumentation im Browser

livewire/
    = Fachseiten

routes/design/
    = Designrouting

tests/Feature/Design/
    = Absicherung des Designsystems
```

Wenn eine Änderung nicht eindeutig zugeordnet werden kann, ist das ein Hinweis darauf, dass die Komponente oder Verantwortung noch nicht sauber geschnitten ist.

---

### 5.24 Abhängigkeiten zwischen den Dateien

Typische Komponente:

```text
tokens.css
    ↓
buttons.css
    ↓
Blade-Komponente / Fachseite
    ↓
Designreferenz
```

Portalheader:

```text
tokens.css
    ↓
header.css
    ↓
portal-header.blade.php
    ↕
portal-header.js
    ↓
public.blade.php / design.layout
    ↓
alle Seiten
```

Eine Änderung an einer tiefen Basisebene kann viele Seiten beeinflussen. Deshalb sollen Änderungen an `tokens.css`, `base.css`, `layout.css`, Header und Footer besonders sorgfältig getestet werden.


## 6. Namenskonventionen

Das neue System orientiert sich bei den sichtbaren UI-Klassen stärker am bisherigen VDBS-Portal.

### Block

```css
.btn
.form
.table
.card
.page-title
.site-header
```

### Element mit `__`

```css
.page-title__kicker
.page-title__title
.form__field
.form__label
.form__control
.header-bottom__inner
.site-footer__brand
```

Schema:

```text
.block__element
```

### Modifier mit `--`

```css
.btn--secondary
.btn--quiet
.grid--3col
.page-title--split
.site-header--wide
.site-header--preview
```

Schema:

```text
.block--modifier
```

### Laufzeitzustand mit `is-`

```css
.is-compact
.is-scrolled
.is-mobile-menu-open
```

### JavaScript-Hooks

JavaScript soll nicht von Styling-Klassen abhängig sein. Dafür bleiben `data-vdbs-*` Attribute:

```text
data-vdbs-portal-header
data-vdbs-mobile-menu-trigger
data-vdbs-mobile-menu
data-vdbs-account-menu
data-vdbs-account-trigger
data-vdbs-account-panel
data-vdbs-submenu
data-vdbs-submenu-trigger
data-vdbs-submenu-panel
```

Regel:

- CSS-Klasse = Darstellung
- `data-vdbs-*` = Verhalten
- ARIA = semantischer Zustand

### Bestehende `.vdbs-*` Klassen

Die erste Laravel-Designsystem-Version verwendete einen `vdbs-` Präfix. Diese Klassen bleiben als Kompatibilitätsalias bestehen. Neue Kernkomponenten können die kürzeren portalähnlichen Klassennamen verwenden.

Beispiel:

```css
.card,
.vdbs-card {
    ...
}
```

---

## 7. Design-Tokens

### Spacing

4-px-Rhythmus:

```css
--spacing-1: 0.25rem;
--spacing-2: 0.5rem;
--spacing-3: 0.75rem;
--spacing-4: 1rem;
--spacing-5: 1.5rem;
--spacing-6: 2rem;
--spacing-7: 2.5rem;
--spacing-8: 3rem;
--spacing-9: 4rem;
--spacing-10: 6rem;
```

### Seitenbreite

Standard:

```css
--layout-content-max: 80rem;
```

ca. 1280 px.

Breit:

```css
--layout-content-wide-max: 90rem;
```

ca. 1440 px.

### Komponenten-Skalen

Wiederkehrende technische Größen werden ebenfalls als Tokens gepflegt:

```css
--border-width-default
--border-width-strong
--border-width-accent

--control-height-sm
--control-height-md
--control-height-lg

--icon-size-sm
--icon-size-md
--icon-size-lg
```

Komponenten sollen diese Werte verwenden, anstatt dieselben Maße lokal
mehrfach neu zu definieren.

### Ebenen und Bewegung

Für wiederkehrende UI-Ebenen stehen zentrale Z-Index-Tokens zur Verfügung:

```css
--z-header
--z-dropdown
--z-popover
--z-overlay
--z-skip-link
```

Animationen und Übergänge bleiben dezent. Bei
`prefers-reduced-motion: reduce` werden die zentralen Übergangszeiten
auf `0ms` gesetzt.

### Seitenrand

```css
--layout-page-gutter: clamp(2rem, 4vw, 3.5rem);
```

Desktop etwa 32–56 px.

### Schatten

```css
--shadow-sm
--shadow-md
--shadow-lg
```

`sm` für leichte Abhebung, `md` für deutliche Komponenten, `lg` für Overlay/Dropdown/Sticky-Ebene.

---

## 8. Globale Seitenstruktur

```text
Portal Header
    ↓
Main Content
    ↓
Portal Footer
```

Blade:

```blade
<x-vdbs.portal-header ... />

<main class="site-main">
    <div class="container">
        ...
    </div>
</main>

<x-vdbs.portal-footer ... />
```

Diese Struktur wird für bestehende Identity-Seiten, geschützte Portal-Seiten und den Designbereich verwendet. Der Designbereich ist keine separate Dashboard-Anwendung.

---

## 9. Portal Header

Blade-Komponente:

```text
resources/views/components/vdbs/portal-header.blade.php
```

CSS:

```text
resources/css/vdbs/parts/header.css
```

JavaScript:

```text
resources/js/portal-header.js
```

Referenz:

```text
http://my.vdb.test:8000/design/header
```

### Desktopstruktur

```text
┌──────────────────────────────────────────────────────────────┐
│ [Logo]                              Verwaltung    Design     │
├──────────────────────────────────────────────────────────────┤
│ VDBS Portal       Seitennavigation                 Konto     │
│ Aktuelle Seite                                               │
├──────────────────────────────────────────────────────────────┤
│ VDBS Portal / aktuelle Seite                                 │
└──────────────────────────────────────────────────────────────┘
```

### Obere Ebene

Klassen:

```css
.site-header
.header-top
.header-top__inner
.header-areas
.header-area-link
```

Aufgabe: Logo, Bereiche und später interne/externe Systeme.

### Untere Ebene

```css
.header-bottom
.header-bottom__inner
.header-page-context
.header-page-context__area
.header-page-context__page
.header-nav
.header-nav-link
.header-account
```

Konzept:

```text
Bereich
Konkrete Seite
```

### Untermenüs

Ein Eintrag kann `children` besitzen. Dropdowns sind weiß, eckig, gerahmt und deutlich beschattet.

### Sticky-Verhalten

Beim Herunterscrollen wird die obere Ebene eingeklappt; die untere bleibt sichtbar. Beim Hochscrollen erscheint der vollständige Header wieder.

JavaScript setzt `is-compact`.

---

## 10. Navigation

Aktueller Datenvertrag:

```php
[
    'label' => 'Profil',
    'url' => route('my.profile'),
    'active' => true,
]
```

Nicht implementiert:

```php
[
    'label' => 'Meine Tickets',
    'url' => null,
]
```

Mit Untermenü:

```php
[
    'label' => 'Verwaltung',
    'children' => [
        [
            'label' => 'Mitglieder',
            'url' => route(...),
        ],
    ],
]
```

Die obere Bereichsnavigation soll später aus einer Datenbank kommen. Vor einer Migration müssen Berechtigungen, interne/externe Ziele und Hierarchien fachlich definiert werden.

---

## 11. Mobile Navigation

Auf kleineren Viewports verschwinden beide horizontalen Desktoplisten.

Der Header zeigt:

```text
Logo
Bereich / Seite
Menü
```

Das Hamburger-Menü behält getrennte Gruppen:

```text
NAVIGATION
...

BEREICHE
...

KONTO
...
```

Die Gruppen werden nicht zu einer unstrukturierten Linkliste vermischt.

---

## 12. Benutzerkonto

Angemeldet ersetzt ein Avatar den Login-Button. Ohne Profilbild werden Initialen verwendet.

Menüstruktur:

```text
Mein Profil
Kontoeinstellungen
Meine Tickets
────────────────
Kontakt
FAQ
Hilfe
────────────────
Abmelden
```

Nicht vorhandene Funktionen erhalten `url => null`. Es werden keine Routen erfunden.

---

## 13. Icon-System

Komponente:

```text
resources/views/components/vdbs/icon.blade.php
```

Verwendung:

```blade
<x-vdbs.icon name="user" />
```

oder:

```blade
<x-vdbs.icon name="settings" size="20" />
```

Dadurch bleiben Icons offline, testbar, CDN-unabhängig und austauschbar.

---

## 14. Footer

Komponente:

```text
resources/views/components/vdbs/portal-footer.blade.php
```

CSS:

```text
resources/css/vdbs/parts/footer.css
```

Der Footer ist bewusst schlichter als die alte Portal-Version:

```text
grüne Trennlinie
Logo / Vereinsname / Claim
optionale vorhandene Links
Meta
```

Nur existierende Ziele werden verlinkt.

---

## 15. Container und Layout

Standard:

```html
<div class="container">
```

Breit:

```html
<div class="container container--wide">
```

Schmaler Text:

```html
<div class="container--narrow">
```

Formular:

```html
<div class="container--form">
```

Vertikaler Abstand:

```html
<div class="stack">
```

Varianten:

```html
<div class="stack stack--sm">
<div class="stack stack--lg">
```

Horizontale Gruppe:

```html
<div class="cluster">
```

---

## 16. Page Title

```html
<header class="page-title">
    <p class="page-title__kicker">Konto</p>
    <h1 class="page-title__title">Mein Profil</h1>
    <p class="page-title__lead">
        Persönliche Daten verwalten.
    </p>
</header>
```

Mit Aktionen:

```html
<header class="page-title page-title--split">
    <div>
        ...
    </div>

    <div class="page-title__actions">
        <button>Neu erstellen</button>
    </div>
</header>
```

Der Header-Kontext ist Orientierung; der H1 im Inhalt bleibt die semantische Hauptüberschrift.

---

## 17. Buttons

Primary:

```html
<button class="btn">Speichern</button>
```

Secondary:

```html
<button class="btn btn--secondary">Zurück</button>
```

Quiet:

```html
<a class="btn btn--quiet" href="#">Abbrechen</a>
```

Ocean:

```html
<button class="btn btn--ocean">Aktion</button>
```

Bestehende bare Buttons innerhalb von `site-main` werden als Primary dargestellt, damit bestehende Volt-Seiten kompatibel bleiben.

---

## 18. Formulare

```html
<form class="form">
    <div class="form__field">
        <label class="form__label" for="name">Name</label>
        <input class="form__control" id="name" type="text">
        <p class="form__help">Ergänzende Information.</p>
    </div>
</form>
```

Bestehende `.field`-Strukturen bleiben kompatibel.

Controls sind eckig, ausreichend groß und haben einen sichtbaren Fokus.

---

## 19. Cards, Panels und Teaser

Card:

```html
<div class="card">
```

Panel:

```html
<div class="panel">
```

Akzent:

```html
<div class="card card--accent">
```

Für Informationsübersichten werden lineare Teaser bevorzugt:

```html
<div class="teaser-list">
    <article class="teaser">
        <h3><a href="#">Mitgliederverwaltung</a></h3>
        <p>Beschreibung.</p>
    </article>
</div>
```

---

## 20. Hinweise

```html
<div class="notice">
<div class="notice notice--success">
<div class="notice notice--warning">
<div class="notice notice--danger">
```

Semantisch ergänzen mit `role="status"` oder `role="alert"`.

---

## 21. Tabellen

```html
<div class="table-wrapper">
    <table class="table">
        ...
    </table>
</div>
```

Tabellen bleiben Tabellen und werden nicht aus rein visuellen Gründen in Cards umgewandelt.

---

## 22. Designbereich

```text
http://my.vdb.test:8000/design
```

Unterseiten:

```text
http://my.vdb.test:8000/design/grundlagen
http://my.vdb.test:8000/design/elemente
http://my.vdb.test:8000/design/vorlagen
http://my.vdb.test:8000/design/header
```

Wichtig:

> Der Designbereich besitzt kein permanentes Seitenmenü links.

Navigation erfolgt über denselben horizontalen Portalheader wie in der Anwendung.

---

## 23. Neue Designseite

```cmd
php artisan make:design-page buttons --title="Buttons"
```

Erzeugt:

```text
resources/views/design/pages/buttons.blade.php
routes/design/pages/buttons.php
```

URL:

```text
http://my.vdb.test:8000/design/buttons
```

Nested:

```cmd
php artisan make:design-page formulare/textfelder --title="Textfelder"
```

Die Seite erscheint automatisch in der horizontalen Designnavigation.

---

## 24. Empfohlenes Designseiten-Grundgerüst

```blade
@extends('design.layout')

@section('title', 'Buttons')

@section('content')
    <div class="stack stack--lg">
        <header class="page-title">
            <p class="page-title__kicker">Komponenten</p>
            <h1 class="page-title__title">Buttons</h1>
            <p class="page-title__lead">
                Beschreibung.
            </p>
        </header>

        <section class="stack">
            <h2>Standard</h2>

            <div class="design-example">
                ...
            </div>
        </section>

        <section class="section stack">
            <h2>Regeln</h2>
            ...
        </section>
    </div>
@endsection
```

---

## 25. Bestehende Seiten

Die Identity-/Volt-Seiten nutzen:

```text
resources/views/components/layouts/public.blade.php
```

Dort wird der Portalrahmen zentral angewendet. Login, Registrierung, Passwort, Profil, E-Mail, Sicherheit und Kontolöschung erhalten dadurch denselben Header, Footer, Seitenabstand, Formular- und Kartenstil, ohne Fachlogik zu duplizieren.

---

## 26. JavaScript

Einstieg:

```text
resources/js/app.js
```

Header:

```text
resources/js/portal-header.js
```

Das Header-Skript übernimmt Scrollrichtung, Sticky-Zustand, Mobile-Menü, Account-Menü, Untermenüs, Escape, Klick außerhalb, ARIA-States und Livewire-Reinitialisierung.

Neue größere Interaktionen erhalten eigene Dateien.

---

## 27. Barrierefreiheit

Fokuszustände nicht entfernen.

Aktive Navigation:

```html
aria-current="page"
```

Dropdown:

```html
aria-expanded="true"
```

Nicht vorhandene echte Links werden als deaktivierter Text ausgegeben, nicht mit erfundenem Ziel.

Icons ohne sichtbaren Text benötigen einen zugänglichen Namen.

Status nie ausschließlich über Farbe kommunizieren.

ARIA-Rollen bestimmen nicht die sichtbare Statusfarbe. Beispielsweise kann
`role="alert"` sowohl bei einer Warnung als auch bei einem Fehler sinnvoll
sein. Die sichtbare Bedeutung wird deshalb durch die jeweilige
Designsystem-Komponente bzw. ihren Modifier festgelegt, zum Beispiel
`notice--warning` oder `notice--danger`.

---

## 28. Tailwind

Tailwind bleibt verfügbar.

Brandfarben:

```html
bg-vdbs-green
text-vdbs-ink
```

Faustregel:

- wiederverwendbares Produktmuster → Komponentenklasse,
- einmalige lokale Layoutanpassung → Tailwind Utility möglich.

---

## 29. Was ändere ich wo?

| Aufgabe | Datei |
|---|---|
| Markenfarbe | `resources/css/vdbs/tokens.css` |
| Spacing | `resources/css/vdbs/tokens.css` |
| Seitenbreite | `resources/css/vdbs/tokens.css` |
| Typografie | `resources/css/vdbs/base.css` |
| Inhaltslayout | `resources/css/vdbs/layout.css` |
| Header HTML | `resources/views/components/vdbs/portal-header.blade.php` |
| Header Styling | `resources/css/vdbs/parts/header.css` |
| Header Verhalten | `resources/js/portal-header.js` |
| Footer HTML | `resources/views/components/vdbs/portal-footer.blade.php` |
| Footer Styling | `resources/css/vdbs/parts/footer.css` |
| Buttons | `resources/css/vdbs/components/buttons.css` |
| Formulare | `resources/css/vdbs/components/forms.css` |
| Grids | `resources/css/vdbs/components/grids.css` |
| Links/Breadcrumb | `resources/css/vdbs/components/links.css` |
| Cards/Teaser | `resources/css/vdbs/components/cards.css` |
| Hinweise | `resources/css/vdbs/components/notices.css` |
| Tabellen | `resources/css/vdbs/components/tables.css` |
| Designbereich / Vorschauhilfen | `resources/css/vdbs/design.css` |
| Icon | `resources/views/components/vdbs/icon.blade.php` |
| Design Layout | `resources/views/design/layout.blade.php` |
| neue Designseite | `php artisan make:design-page ...` |

---

## 30. Neue Komponente entwickeln

Beispiel Pagination:

1. Designseite erzeugen.
2. Zustände und Accessibility definieren.
3. `resources/css/vdbs/components/pagination.css` anlegen.
4. In `resources/css/app.css` importieren.
5. BEM-artige Namen nutzen:
   ```css
   .pagination
   .pagination__list
   .pagination__link
   .pagination__link--active
   ```
6. Bei komplexem Markup Blade-Komponente anlegen.
7. Bei Interaktion eigene JS-Datei ergänzen.
8. Tests schreiben.
9. Erst danach in Fachseiten einsetzen.

---

## 31. Was vermieden werden soll

Keine zufälligen Modulklassen wie:

```css
.profile-green-box-2
```

Keine zufälligen Abstände, wenn Tokens reichen.

Keine erfundenen Routen.

Keine Domainlogik in CSS oder Layout.

Keine externe CDN-Abhängigkeit für Kernicons ohne bewusste Architekturentscheidung.

Keine dauerhafte linke Navigation als globales Portal-Grundlayout.

---

## 32. Qualitätschecks

Design:

```cmd
php artisan test tests\Feature\Design\DesignSystemPageTest.php
php artisan test tests\Feature\Design\MakeDesignPageCommandTest.php
php artisan test tests\Feature\Design\PortalHeaderDesignPageTest.php
php artisan test tests\Feature\Design\PortalLayoutDesignTest.php
```

Frontend:

```cmd
npm run build
```

Caches:

```cmd
php artisan optimize:clear
```

Gesamtsuite:

```cmd
php artisan test
```

Format:

```cmd
php vendor\bin\pint --test
```

Diff:

```cmd
git diff --check
```

Status:

```cmd
git status --short
```

---

## 33. Lokale URLs

Immer mit Port `8000`:

```text
http://my.vdb.test:8000/design
http://my.vdb.test:8000/design/grundlagen
http://my.vdb.test:8000/design/elemente
http://my.vdb.test:8000/design/vorlagen
http://my.vdb.test:8000/design/header

http://my.vdb.test:8000/anmelden
http://my.vdb.test:8000/registrieren
http://my.vdb.test:8000/profil
http://my.vdb.test:8000/profil/email
http://my.vdb.test:8000/profil/passwort
http://my.vdb.test:8000/profil/sicherheit
http://my.vdb.test:8000/profil/konto-loeschen
```

---

## 34. Zielbild

```text
VDBS Corporate Design
        ↓
Tokens
        ↓
Base
        ↓
Layout
        ↓
Header / Footer
        ↓
Komponenten
        ↓
Vorlagen
        ↓
Fachseiten
```

Die visuelle Identität entsteht durch konsequent wiederkehrende Abstände, Linien, Typografie, Navigation, Farben, Komponenten und Informationshierarchie — nicht durch möglichst viele dekorative Effekte.
