# VDBS Web Content Bibliothek — vollständiges Inventar

Dieses Dokument bildet den Stand der zentral gepflegten Web-Content-Bibliothek ab.
Die maschinenlesbare Quelle bleibt `config/web_content_library.php`.

**Inventarisierte Bibliothekseinträge:** 44

## Grundlagen (5)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `design-tokens` | Design Tokens | `stable` | `resources/css/vdbs/tokens.css` |
| `base-typography` | Basis & Typografie | `stable` | `resources/css/vdbs/base.css` |
| `layout-system` | Layout & Raster | `stable` | `resources/css/vdbs/layout.css` |
| `accessibility` | Barrierefreiheit | `stable` | `resources/css/vdbs/a11y.css` |
| `print-system` | Print | `stable` | `resources/css/vdbs/print.css` |

## Elemente (9)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `button` | Buttons | `stable` | `resources/css/vdbs/components/buttons.css` |
| `icon` | Icons | `stable` | `resources/views/components/vdbs/icon.blade.php` |
| `badge` | Badge | `stable` | `resources/views/components/vdbs/badge.blade.php` |
| `status` | Status | `stable` | `resources/views/components/vdbs/status.blade.php` |
| `notice` | Hinweise | `stable` | `resources/views/components/vdbs/notice.blade.php` |
| `forms` | Formulare | `stable` | `resources/css/vdbs/components/forms.css` |
| `loading-state` | Loading State | `stable` | `resources/views/components/vdbs/loading-state.blade.php` |
| `cards` | Karten & Flächen | `stable` | `resources/css/vdbs/components/cards.css` |
| `links` | Links | `stable` | `resources/css/vdbs/components/links.css` |

## Muster (11)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `validation-summary` | Validierungsübersicht | `stable` | `resources/views/components/vdbs/validation-summary.blade.php` |
| `empty-state` | Empty State | `stable` | `resources/views/components/vdbs/empty-state.blade.php` |
| `disclosure` | Disclosure | `stable` | `resources/css/vdbs/components/disclosure.css` |
| `navigation` | Navigation | `stable` | `resources/css/vdbs/components/navigation.css` |
| `search-filter` | Suche & Filter | `stable` | `resources/css/vdbs/components/search.css` |
| `tables` | Tabellen | `stable` | `resources/css/vdbs/components/tables.css` |
| `dialog` | Dialog | `stable` | `resources/views/components/vdbs/dialog.blade.php` |
| `danger-zone` | Danger Zone | `stable` | `resources/views/components/vdbs/danger-zone.blade.php` |
| `records` | Datensatzlisten | `stable` | `resources/css/vdbs/components/records.css` |
| `portal-header` | Portal Header | `stable` | `resources/views/components/vdbs/portal-header.blade.php` |
| `portal-footer` | Portal Footer | `stable` | `resources/views/components/vdbs/portal-footer.blade.php` |

## Inhalte (7)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `metadata` | Metadaten | `stable` | `resources/css/vdbs/components/metadata.css` |
| `media` | Medien | `stable` | `resources/css/vdbs/components/media.css` |
| `file-item` | Datei & Download | `stable` | `resources/views/components/vdbs/file-item.blade.php` |
| `news-teaser` | News-Teaser | `stable` | `resources/views/components/vdbs/news-teaser.blade.php` |
| `event-teaser` | Veranstaltungsteaser | `stable` | `resources/views/components/vdbs/event-teaser.blade.php` |
| `contact-block` | Kontaktblock | `stable` | `resources/views/components/vdbs/contact-block.blade.php` |
| `resource-item` | Ressourceneintrag | `stable` | `resources/views/components/vdbs/resource-item.blade.php` |

## Vorlagen (7)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `template-article` | Vorlage · Artikel | `stable` | `resources/views/design/pages/vorlagen/artikel.blade.php` |
| `template-form` | Vorlage · Formular | `stable` | `resources/views/design/pages/vorlagen/formular.blade.php` |
| `template-public-overview` | Vorlage · Öffentliche Übersicht | `stable` | `resources/views/design/pages/vorlagen/oeffentliche-uebersicht.blade.php` |
| `template-event` | Vorlage · Veranstaltung | `stable` | `resources/views/design/pages/vorlagen/veranstaltung.blade.php` |
| `template-admin-list` | Vorlage · Verwaltungsliste | `stable` | `resources/views/design/pages/vorlagen/verwaltung-liste.blade.php` |
| `template-admin-detail` | Vorlage · Verwaltungsdetail | `stable` | `resources/views/design/pages/vorlagen/verwaltung-detail.blade.php` |
| `error-page` | Fehlerseiten | `stable` | `resources/views/components/vdbs/error-page.blade.php` |

## Werkzeuge (5)

| ID | Name | Status | Primärquelle |
| --- | --- | --- | --- |
| `web-content-library` | Web Content Bibliothek | `beta` | `config/web_content_library.php` |
| `code-example` | Code-Beispiel & Copy | `beta` | `resources/views/components/vdbs/code-example.blade.php` |
| `icon-browser` | Icon-Browser | `beta` | `app/Support/VdbsIconCatalog.php` |
| `button-playground` | Button-Playground | `beta` | `app/Support/ButtonExampleBuilder.php` |
| `library-tooling` | Bibliotheks-Werkzeuge | `beta` | `app/Console/Commands/VdbsLibraryCheckCommand.php` |

## Automatische Abdeckung

`php artisan vdbs:library-check` prüft zusätzlich automatisch alle Dateien in:

- `resources/views/components/vdbs/*.blade.php`
- `resources/css/vdbs/components/*.css`

Sobald dort eine neue Datei angelegt wird, ohne dass sie über `source` oder `files`
eines Registry-Eintrags erfasst ist, schlägt die Prüfung fehl. Dadurch wird verhindert,
dass neue Kernbausteine unbemerkt außerhalb der Bibliothek wachsen.

