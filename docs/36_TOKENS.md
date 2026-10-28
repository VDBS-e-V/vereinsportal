# Tokensystem

Quelle: `resources/css/vdbs/tokens.css`. Anzeige in der Design-Bibliothek: `/design/farben`.

## Ebenen

| Ebene | Namen | Regel |
|---|---|---|
| 1. Primitive | `--color-*`, `--alpha-*` | die festen Werte; **nur hier** stehen Farbcodes |
| 2. Semantik | `--bg-*`, `--text-*`, `--border-*`, `--accent-*`, `--group-*`, `--on-dark-*`, `--on-brand-*`, `--scrim-*` | Rollen im Code; verweisen **nur auf Primitive** |
| 3. Komponente | z. B. `--btn-bg`, `--credit-bg`, `--tone` | lokale Variablen in den Komponenten-CSS; verweisen auf Semantik |

Der Dark Mode (`[data-theme="dark"]` und `prefers-color-scheme`) überschreibt nur Semantik, nie Primitive.
Die beiden Blöcke müssen identisch bleiben.

## Entscheidungen

- **Aktion:** `--bg-action` ist das dunkle Grün (`--color-gruen-dunkel`), `--text-on-action` ist Weiß (5,0:1), Hover
  `--bg-action-hover`. Primäre und tertiäre Buttons, Login und Pfeilflächen der Teaser nutzen es.
- **Markengrün:** `--bg-brand` bleibt das helle Grün; Schrift darauf ist immer Ink (`--text-on-brand`), nie Weiß.
- **Flächen mit eigener Farbe:** `--on-dark-*` (Lila, Ink, Bild) und `--on-brand-*` (helles Grün) gelten in beiden Themes
  unverändert; die Button-Kontexte (`.vdbs-on-dark`, `.vdbs-on-brand`) bauen darauf auf.
- **Status:** Schrift, Fläche, Rand und Akzent je Zustand (`--text-error`, `--bg-error-soft`, `--border-warning`, …);
  Hinweise und Badges nutzen nur diese. Im Dark Mode stehen die hellen Varianten bereit.
- **Gruppenfarben:** `--group-1..6` mit der Schrift `--text-on-group-light/-dark`.
- **Formen:** Radius 0, nur `--radius-full` für Pills und Avatare. Eckig ist die Portal-Abweichung vom Styleguide.
- **Größen:** `--size-touch` (44 px) für alle Bedienelemente, `--border-width`, `--border-width-strong`, `--focus-width`.
- **Ebenen:** `--z-raised`, `--z-overlay`, `--z-link`, `--z-dropdown`, `--z-sticky`.
- **Breiten:** Container `narrow/medium/default/wide`, `--gutter`; Breakpoints als Zahlen in `--bp-*` (in Media Queries
  nicht verwendbar, dort steht der Wert, z. B. 1100 px für die Navigation).
- **Dark Mode:** Rahmen `--border-input` ist `--color-mauve-500` (mind. 3:1, Abweichung vom Styleguide).

## Neue Farbe hinzufügen

1. Als Primitive in Ebene 1 anlegen (`--color-…`, Alpha als `--alpha-…`).
2. Eine Rolle (Ebene 2) darauf zeigen lassen; bei Themen-Unterschied im Dark-Block **beider** Selektoren überschreiben.
3. Im Komponenten-CSS nur die Rolle verwenden.
4. `python scripts/token_contrast.py`, `button_contrast.py` und `form_contrast.py` laufen lassen; `vendor\bin\pest` prüft die Regeln.
