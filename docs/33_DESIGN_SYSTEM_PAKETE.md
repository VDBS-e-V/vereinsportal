# Design-System: Pakete und Dateiaufbau

Jedes Paket hat einen Ordner unter `resources/css/vdbs/components/<paket>/` (mit `index.css`)
und `resources/views/components/vdbs/<paket>/`. Eine Komponente = eine Blade-Datei + eine CSS-Datei.

| Paket | Blade-Namespace | Inhalt (Stand) |
| --- | --- | --- |
| foundation | `x-vdbs.icon` | Icons |
| actions | `x-vdbs.actions.*` | button (einziges Button-System), button-group, link-button (Kurzform), back-to-top |
| forms | `x-vdbs.forms.*` | input, select, textarea |
| navigation | `x-vdbs.navigation.*` | portal-header, portal-footer, nav-main, breadcrumb |
| layout | `x-vdbs.layout.*` | container, section, split, grid, aside-layout (Bild/Fläche/Seitenleiste neben dem Inhalt), Seitenrahmen, Druck |
| content | `x-vdbs.content.*` | feature (Inhaltsblock: side, overlap, offset, links; Töne none, surface, soft, dark, brand) |
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

## Paket Formulare

| Baustein | Aufruf | Zweck |
| --- | --- | --- |
| input | `<x-vdbs.forms.input>` | Text, E-Mail, Telefon, URL, Zahl, Datum/Zeit; Größen s/m/l, Vorsatz/Nachsatz, Zähler |
| password | `<x-vdbs.forms.password>` | Passwort mit Anzeigen/Verbergen, Regeln |
| code | `<x-vdbs.forms.code>` | Ein-Mal-Code (2FA), 4–10 Zeichen |
| textarea, select, multi-select | `<x-vdbs.forms.textarea>`, `<x-vdbs.forms.select>`, `<x-vdbs.forms.multi-select>` | Mehrzeilig, Auswahlliste (Gruppen), Mehrfachauswahl mit Chips und Filter |
| checkbox, checkbox-group, radio-group, switch | `<x-vdbs.forms.checkbox>` usw. | Auswahlfelder, Optionskarten, Schalter |
| file | `<x-vdbs.forms.file>` | Upload (Feld, Dropzone) |
| search | `<x-vdbs.forms.search>` | Suchformular mit Knopf |
| form, form-grid, form-section, fieldset, error-summary | `<x-vdbs.forms.form>` usw. | Formularaufbau und Fehlerübersicht |

Gemeinsames Verhalten (App\Support\Forms\FieldState): Id `field-<name>`, Fehlermeldung aus dem Validierungs-Bag,
`old()`-Wert, `aria-describedby` für Hinweis/Fehler/Erfolg, `class` geht an den Feld-Rahmen, alle anderen
Attribute an das Steuerelement. Pflicht/optional werden als Text gekennzeichnet. JavaScript (resources/js/forms.js)
ist reine Verbesserung. Kontrastprüfung: `python scripts/form_contrast.py`.
