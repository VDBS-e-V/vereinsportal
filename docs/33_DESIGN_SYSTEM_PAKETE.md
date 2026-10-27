# Design-System: Pakete und Dateiaufbau

Jedes Paket hat einen Ordner unter `resources/css/vdbs/components/<paket>/` (mit `index.css`)
und `resources/views/components/vdbs/<paket>/`. Eine Komponente = eine Blade-Datei + eine CSS-Datei.

| Paket | Blade-Namespace | Inhalt (Stand) |
| --- | --- | --- |
| foundation | `x-vdbs.icon` | Icons |
| actions | `x-vdbs.actions.*` | button (einziges Button-System), button-group, link-button (Kurzform), back-to-top |
| forms | `x-vdbs.forms.*` | input, select, textarea |
| navigation | `x-vdbs.navigation.*` | portal-header, portal-footer, nav-main, breadcrumb |
| layout | `x-vdbs.layout.*` | section, split, grid (+ Seitenrahmen, Druck) |
| cards | `x-vdbs.cards.*` | card, quick-links, quick-link, teaser, contact-block |
| data | `x-vdbs.data.*` | table, badge, group-mark, chip-list, chip |
| feedback | `x-vdbs.feedback.*` | alert, error-page |
| overlays | `x-vdbs.overlays.*` | modal |
| media | `x-vdbs.media.*` | hero, hero-tile, figure |

Regeln: eine Komponente pro Datei (CSS möglichst unter ~100 Zeilen), BEM-Klassen `.vdbs-name__teil--variante`,
nur semantische Tokens, jede Komponente bekommt einen Eintrag in `app/Modules/DesignLibrary/Support/Registry.php`
und einen Test. Alte, noch nicht überarbeitete CSS-Dateien liegen unter `components/_legacy/`.

## Kontrastprüfung Buttons

`python scripts/button_contrast.py` (Windows: `py scripts\button_contrast.py`) rechnet für alle Kombinationen aus
Thema (hell/dunkel), Fläche (default/dark/brand), Rolle, Form und Zustand (Ruhe, Hover, Gedrückt) nach:
Text mindestens 4,5:1, Rahmen/Linie mindestens 3:1. Mit `--all` werden auch bestandene Fälle gelistet.
Deaktivierte Buttons sind nach WCAG ausgenommen. Nach Änderungen an `tokens.css`, `button-variants.css`
oder `button-contexts.css` erneut ausführen.
