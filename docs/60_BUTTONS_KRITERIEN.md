# Buttons: Quellen, Erfolgskriterien und Systemvorschlag

Stand: Entwurf für die gemeinsame Umgestaltung des Pakets Aktionen. Bild: `docs/design/buttons-system.png` (Quelltext `docs/design/buttons-system.html`).

## Quellen
- Homepage-Helden, „Perfekte Buttons“: konnte aus der Sitzung **nicht abgerufen** werden (Host nicht freigegeben). Die Punkte daraus sind noch nicht eingearbeitet.
- W3C/WCAG 2.2: [What’s New in WCAG 2.2](https://www.w3.org/WAI/standards-guidelines/wcag/new-in-22/), [WebAIM zu WCAG 2.2](https://webaim.org/standards/wcag/wcag22): Zielgröße (Minimum) 24 × 24 px (2.5.8, AA), Kontrast von Bedienelementen und Fokus 3 : 1 (1.4.11, AA), Fokus sichtbar (2.4.7). 44 × 44 px ist die erweiterte Stufe (2.5.5, AAA).
- Designsysteme: [Morningstar](https://design.morningstar.com/systems/marketing/business-website/components/buttons), [Michigan](https://digitalguidelines.michigan.gov/docs/components/button/som-button), [BASF GROW](https://grow-brandhub.agro.basf.com/design/components/buttons), [Scottish Government](https://designsystem.gov.scot/components/button), [NHS](https://service-manual.nhs.uk/design-system/components/buttons), [GOV.UK-Frontend](https://cdn.jsdelivr.net/npm/@penx/govuk-frontend@0.0.2/components/button/README.md), [Wikimedia Codex](https://doc.wikimedia.org/codex/v1.0.0/components/demos/button.html), [Material 3 (Implementierungen)](https://developer.android.com/reference/kotlin/androidx/compose/material3/FilledTonalIconButton.composable).
- Praxisartikel: [UXPin](https://www.uxpin.com/studio/blog/button-design), [21st.dev](https://21st.dev/blog/react-button-components), [LogRocket zu Zuständen](https://blog.logrocket.com/ux-design/designing-button-states), [Slickplan](https://slickplan.com/blog/designing-buttons-for-the-web-guide-examples-for-2020).

Hinweis: Mehrere Werte (44 px, 8 px Abstand, Beschriftungslänge) stammen aus Designsystemen und Artikeln, nicht aus Normen. Die Werte aus WCAG sind verbindlich.

## Erfolgskriterien (Merkmale eines guten Button-Systems)
1. **Eindeutige Hierarchie:** fünf Rollen (Primär, Sekundär, Tertiär mit Rand, Text, Gefahr); pro Ansicht höchstens eine Primär-Aktion; Gefahr abgesetzt, nie vorausgewählt.
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

## Systemvorschlag (Bild)
Farben (aus `tokens.css`): Primär `--bg-action` (#057f5b, Weiß 5,0 : 1), Hover #046a4d (6,6), Gedrückt #035340 (9,1); Sekundär Ink-Rand auf Weiß (17,9); Tertiär Aktionsgrün als Rand und Schrift (5,0); Gefahr #b42318 (6,6). Dunkelmodus: helles Grün #40cd9a mit dunkler Schrift (9,3), Rand mauve (4,8). Text-Button: Schrift #057f5b auf Weiß 5,0 : 1, Hover #046a4d auf #dcf8ea 5,9 : 1, gedrückt #035340 auf #c3ecd6. Neu gegenüber den Tokens sind nur die Zustände „Gedrückt“ die hellgrünen Flächen des Text-Buttons und die graue Fläche für „Deaktiviert“; sie müssten als Tokens angelegt werden.
