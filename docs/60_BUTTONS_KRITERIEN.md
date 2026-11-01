# Buttons: Quellen, Erfolgskriterien und Systemvorschlag

Stand: Entwurf für die gemeinsame Umgestaltung des Pakets Aktionen. Bild: `docs/design/buttons-system.png` (Quelltext `docs/design/buttons-system.html`).

## Quellen
- Homepage-Helden, „Perfekte Buttons“: konnte aus der Sitzung **nicht abgerufen** werden (Host nicht freigegeben). Die Punkte daraus sind noch nicht eingearbeitet.
- W3C/WCAG 2.2: [What’s New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/), [WebAIM zu WCAG 2.2](https://webaim.org/standards/wcag/wcag22): Zielgröße (Minimum) 24 × 24 px (2.5.8, AA), Kontrast von Bedienelementen und Fokus 3 : 1 (1.4.11, AA), Fokus sichtbar (2.4.7). 44 × 44 px ist die erweiterte Stufe (2.5.5, AAA).
- Designsysteme: [Morningstar](https://design.morningstar.com/systems/marketing/business-website/components/buttons), [Michigan](https://digitalguidelines.michigan.gov/docs/components/button/som-button), [BASF GROW](https://grow-brandhub.agro.basf.com/design/components/buttons), [Scottish Government](https://designsystem.gov.scot/components/button), [NHS](https://service-manual.nhs.uk/design-system/components/buttons), [GOV.UK-Frontend](https://cdn.jsdelivr.net/npm/@penx/govuk-frontend@0.0.2/components/button/README.md), [Wikimedia Codex](https://doc.wikimedia.org/codex/v1.0.0/components/demos/button.html), [Material 3 (Implementierungen)](https://developer.android.com/reference/kotlin/androidx/compose/material3/FilledTonalIconButton.composable).
- Praxisartikel: [UXPin](https://www.uxpin.com/studio/blog/button-design), [21st.dev](https://21st.dev/blog/react-button-components), [LogRocket zu Zuständen](https://blog.logrocket.com/ux-design/designing-button-states), [Slickplan](https://slickplan.com/blog/designing-buttons-for-the-web-guide-examples-for-2020).

Hinweis: Mehrere Werte (44 px, 8 px Abstand, Beschriftungslänge) stammen aus Designsystemen und Artikeln, nicht aus Normen. Die Werte aus WCAG sind verbindlich.

## Erfolgskriterien (Merkmale eines guten Button-Systems)
1. **Eindeutige Hierarchie:** sechs Rollen (Primär Grün, Sekundär Lila, Tertiär mit Rand, Text, Gefahr, Gefahr als Text); pro Ansicht höchstens eine Primär-Aktion; Gefahr abgesetzt, nie vorausgewählt.
2. **Kontrast Schrift ≥ 4,5 : 1** (WCAG 1.4.3) in jedem Zustand außer „Deaktiviert“ (dort ausgenommen, wir halten trotzdem 4,5 : 1).
3. **Kontrast von Fläche oder Rand ≥ 3 : 1** gegen die Umgebung (WCAG 1.4.11), im hellen und im dunklen Modus.
4. **Zielgröße:** Standard 44 px hoch, Icon-Button 44 × 44 px, kompakt 36 px (nur in dichten Tabellen); nie unter 24 px (WCAG 2.5.8); Abstand zwischen Buttons ≥ 8 px.
5. **Alle Zustände:** Standard, Hover, Fokus, Gedrückt, Deaktiviert, Lädt.
6. **Sichtbarer Fokus:** 3 px Ring mit 2 px Abstand, ≥ 3 : 1, unabhängig von der Buttonfarbe.
7. **Klare Beschriftung:** Verb am Anfang, ein bis drei Wörter, kein Umbruch, in der Oberfläche „Sie“; Icon-Buttons haben einen zugänglichen Namen und Tooltip.
8. **Zustand nie nur durch Farbe:** „Deaktiviert“ und „Lädt“ tragen Text oder Spinner.
9. **Kein Layoutsprung:** „Lädt“ hat dieselbe Breite wie „Standard“; Mehrfachklicks sind gesperrt.
10. **Deaktiviert erklärt sich:** Wo möglich bleibt der Button aktiv und zeigt beim Klick den Grund (Michigan); sonst `aria-disabled` mit Hinweis.
11. **Richtige Semantik:** Aktionen sind `button`, Navigation ist `a` im Button-Stil.
12. **Eine Grammatik:** Rolle × Größe × Form × Fläche; eckige Ecken; 4-px-Raster; nur semantische Tokens; Dunkelmodus und farbige Flächen inklusive.
13. **Icons mit und ohne Text:** jede Rolle funktioniert in vier Formen: nur Text, Icon + Text, Text + Icon, nur Icon. Alle sind 44 px hoch; Icon-Buttons 44 × 44 px mit `aria-label` und Tooltip; Icons sind dekorativ (`aria-hidden`), wenn Text daneben steht.
14. **Text-Button:** niedrigste Betonung ohne Rand und Fläche. Er bleibt als Bedienelement erkennbar: Schrift in Aktionsgrün (5,0 : 1), 2 px unterstrichen (nicht nur Farbe, WCAG 1.4.1), Hover mit heller Fläche (5,9 : 1) und dickerer Linie, Fokusring wie alle. Gleiche Zielgröße (44 px) wie die anderen Rollen.
15. **Responsiv:** auf dem Handy volle Breite und gestapelt, Primär zuerst.

## Systemvorschlag (Bild, Stand: Definitionen des Auftraggebers)
Vier Rollen, jede in zwei Farben (Grün = Aktion `#057f5b`, Lila = Marke `#58275a`):

| Rolle | Definition | Grün | Lila |
|---|---|---|---|
| Primär | dunkle Fläche, weiße Schrift | `#057f5b` (Hover `#046a4d`, gedrückt `#035340`) | `#58275a` (Hover `#451d47`, gedrückt `#341534`) |
| Sekundär | helle Fläche, Ink-Schrift | `#dcf8ea` (Hover `#c3ecd6`, gedrückt `#a8e6cf`) | `#efe6f0` (Hover `#e0cfe2`, gedrückt `#d0b6d3`) |
| Tertiär | weiß, Schrift und Rand in Farbe | Schrift/Rand `#057f5b`, Hover-Fläche `#dcf8ea` | Schrift/Rand `#58275a`, Hover-Fläche `#efe6f0` |
| Text | nur Schrift in Farbe | `#057f5b`, Hover mit Fläche und Unterstrich | `#58275a`, ebenso |

Dunkelmodus: hellere Töne derselben Farbe (Grün `#40cd9a`, Lila `#d7b4da`) mit dunkler Schrift auf Primär; Sekundär mit dunkler Tönung und Nebel-Schrift; weißer Fokusring. Alle Schrift-Kontraste ≥ 4,5 : 1 (gerechnet), Rand und Primär-Fläche ≥ 3 : 1.

Offen: Gefahr (rot) und Gefahr-Text sind in dieser Fassung nicht enthalten. Hinweis: Die Sekundär-Fläche hebt sich nur schwach von Weiß ab (≈ 1,1 : 1); der Button wird über Beschriftung und Größe erkannt. Der Text-Button hat im Ruhezustand keinen Unterstrich (Definition „nur Text in Farbe“); erst bei Hover.

## Umsetzung im Code (Stand)
- `<x-vdbs.actions.button>`: neue Eigenschaft `tone="green|lila"` (Standard Grün) und neue Rolle `variant="text"` (`quiet` bleibt als alter Name). Jede Rolle bringt ihre Form mit (primary und secondary gefüllt, tertiary mit Rahmen, text ohne Rahmen). `shape` ist nur für Sonderformen da: `arrow`, `line`, `link` und `outline` (Gefahr).
- Tokens (`tokens.css`): `--btn-green-*`, `--btn-lila-*` (solid, soft, text je mit hover und press), `--btn-on-solid`, `--btn-on-soft`, `--btn-disabled-*`, hell und in beiden Dark-Blöcken. Primitive Farben sind dort ergänzt.
- CSS: `button-variants.css` (Tonfarben und Rollen), `button-shapes.css` (Form `text`), `button-states.css` (Gedrückt, Deaktiviert).
- Migration der Seiten: `variant="tertiary"` (Textlink-Stil) → `text`; `variant="secondary" shape="outline"` → `tertiary`; `quiet` → `text`. Alle übrigen Sekundär-Buttons sind jetzt helle Flächen. Auf dunklen und Markenflächen (`on="dark|brand"`) gilt die bisherige Zuordnung weiter.
- Beispielseite: `/design/buttons` (Rollen × Tonfarben, Zustände, Icon-Formen, Formen, Flächen).

## Dunkelmodus: Quellen, Erfolgskriterien, Entwurf
Entwurf: `docs/design/buttons-system-dunkel.png` (Quelltext `buttons-system-dunkel.html`). Noch nicht im Code.

### Quellen
- [Material Design: Dark theme](https://m2.material.io/design/color/dark-theme): dunkelgraue Flächen statt Schwarz, Akzente entsättigen, 4,5 : 1 auf allen Erhebungsstufen, Erhebung durch hellere Flächen.
- [Apple HIG: Dark Mode](https://developer.apple.com/design/human-interface-guidelines/dark-mode.md): Basis- und erhöhte Hintergründe, Farben passen sich an und sind keine reine Umkehrung, erhöhten Zustand testen.
- [Accessibility Checker: Dark Mode](https://www.accessibilitychecker.org/blog/dark-mode-accessibility/), [DubBot](https://dubbot.com/dubblog/2023/dark-mode-a11y.html), [Uxcel](https://uxcel.com/blog/12-principles-of-dark-mode-design-627): 3 : 1 für Bedienelemente und 4,5 : 1 für Schrift (WCAG 1.4.3, 1.4.11); hohe Sättigung heißt nicht hoher Kontrast.
- Nielsen Norman Group (nur über Zusammenfassungen gefunden, Originale prüfen): Dunkelmodus ist vor allem eine Präferenz, kein Gewinn für alle Nutzer; deshalb Wahlmöglichkeit.

### Erfolgskriterien
1. Gleiche Logik wie hell: Primär = gesättigte Fläche + weiße Schrift (nicht invertieren, nicht pastell).
2. Gedämpfte Akzente: mittlere Helligkeit, keine Neon- oder Pastelltöne.
3. Schrift ≥ 4,5 : 1 in allen Zuständen.
4. Primär-Fläche gegen die Seite ≥ 3 : 1 (alle Zustände).
5. Lesbar auf Seite, Karte und erhöhter Fläche (Schrift ≥ 4,5 : 1).
6. Dunkelgrau statt Schwarz; Tiefe durch hellere Flächen.
7. Hover und Gedrückt sichtbar (dunkler, Weiß-Kontrast steigt), weißer Fokusring ≥ 3 : 1.
8. Deaktiviert lesbar (≥ 4,5 : 1) und erkennbar gedämpft.
9. Wenig Akzent: eine Primär-Aktion pro Ansicht.
10. Hell/Dunkel bleibt Nutzerwahl.

### Entwurf (Werte)
| | Fläche | Hover | Gedrückt | Schrift/Rand (Tertiär, Text) | Sekundär (Fläche/Hover/Gedrückt) |
|---|---|---|---|---|---|
| Lila | `#a455af` | `#9b4ea5` | `#8e4797` | `#c777d2` (Hover `#d394db`) | `#381d39` / `#4a2a4b` / `#5c395d` |
| Grün | `#1f8362` | `#1d7a5b` | `#1a7054` | `#2aa87e` (Hover `#30bf90`) | `#0f3a2c` / `#14503c` / `#1a6650` |

Schrift: Primär weiß (4,7–6,0 : 1), Sekundär Nebel `#f1f4f2`. Flächen: Seite `#180f18`, Karte `#261926`, erhöht `#381d39`. Primär-Fläche zur Seite 3,1–4,0 : 1; auf der erhöhten Fläche (`#381d39`) bleibt der Flächenkontrast unter 3 : 1, die Schrift (≥ 4,5 : 1) und der Fokusring tragen dort die Erkennbarkeit.

### Neufassung: Dunkelmodus mit neutralem Hintergrund (ersetzt den Entwurf mit Aubergine)
Bild: `docs/design/buttons-system-dunkel-neutral.png`. Entscheidung: Der Dunkelmodus bekommt keinen Lila-Stich im Hintergrund mehr. Lila und Grün sind reine Akzentfarben auf neutralem Graphit. Umgesetzt in `tokens.css` (Dark-Blöcke); Test: `tests/Feature/DesignLibrary/DarkModeTokensTest.php`. Zusätzlich gewählt (nicht im Bild): Trennlinien `--border-default` `#343a40`, gedämpfte Schrift `--text-muted` `#9aa1a6`, Rahmen `--border-input` `#80888e`, Kopf- und Fußbereich `#1b1e21`.

| Fläche | Wert |
|---|---|
| Seite | `#121416` |
| Karte | `#1b1e21` |
| Erhöht | `#25292d` |

| | Fläche | Hover | Gedrückt | Schrift/Rand (Tertiär, Text) | Sekundär (Fläche/Hover/Gedrückt) |
|---|---|---|---|---|---|
| Lila | `#a355b2` | `#9b4dab` | `#90479f` | `#c379d2` (Hover `#d095dc`) | `#2d2336` / `#3b2f47` / `#4a3b58` |
| Grün | `#1f8362` | `#1d7a5b` | `#1a7054` | `#2aa87e` (Hover `#30bf90`) | `#173329` / `#1d4536` / `#245a47` |

Gerechnet: weiße Schrift auf Primär 4,7–5,9 : 1; Primär-Fläche zur Seite ≥ 3,1 : 1, zur Karte 3,6 : 1, zur erhöhten Fläche 3,1 : 1 (Standardzustand); Tertiär- und Text-Schrift auf allen drei Flächen ≥ 4,9 : 1; Deaktiviert 5,0 : 1; weißer Fokusring 18,5 : 1.

## Stand der Umstellung auf allen Seiten
- Entfernt: die Formen `arrow`, `line`, `link` und `outline` (Eigenschaft `shape`), der Name `quiet`, die Größe `xl`, die Komponente `link-button` samt Beispielseite und Manifest-Eintrag, die rohen `vdbs-btn`-Klassen in den Seiten.
- Umgestellt: alle Aufrufe (Link-Buttons → Button mit Pfeil-Icon am Ende; Gefahr mit Rahmen → Gefahr gefüllt; Wiederholungs-Buttons und Assistent auf die Komponente).
- Zur Zustimmung offen (Sonderdesigns): Gefahr (rot) als eigene Rolle, die Flächen `dark` und `brand` für Heros, die tote Alt-Ansicht (`resources/views/administration`, `identity`, `layouts/administration`) mit alten `btn`-Klassen.
