# Design-System: Pakete und Dateiaufbau

Jedes Paket hat einen Ordner unter `resources/css/vdbs/components/<paket>/` (mit `index.css`)
und `resources/views/components/vdbs/<paket>/`. Eine Komponente = eine Blade-Datei + eine CSS-Datei.

| Paket | Blade-Namespace | Inhalt (Stand) |
| --- | --- | --- |
| foundation | `x-vdbs.icon` | Icons |
| actions | `x-vdbs.actions.*` | button, link-button, back-to-top |
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
