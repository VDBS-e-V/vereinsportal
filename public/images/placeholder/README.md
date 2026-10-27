# Platzhalterbilder

Eigene Bilder hier ablegen, Namensschema: `<art>-<nn>.<endung>`

| Art | Format | Größe | Verwendung |
| --- | --- | --- | --- |
| hero | 16:7 | 1600×700 | Hero (image, caption) |
| tile | 3:2 | 600×400 | Hero-Kacheln (panel) |
| teaser | 3:2 | 800×533 | Teaser |
| split | 4:3 | 1200×900 | Split-Abschnitte |
| contact | 4:3 | 1200×900 | Kontaktblock |
| portrait | 1:1 | 400×400 | Personen, Avatare |

Beispiele: `hero-01.jpg`, `hero-02.webp`, `teaser-03.png` (Endungen: jpg, jpeg, png, webp, avif, svg).
Nummer zweistellig ab `01`. Ohne eigene Bilder greift der Fallback `<art>.svg`.

Verwendung im Code: `placeholder('hero')`, `placeholder('teaser', 2)` (Blade und PHP).
Übersicht und JSON: `/design/platzhalterbilder` und `/design/api/platzhalter`.

Bestand: siehe `CREDITS.md` (Pexels-Fotos, Originalnamen). Die Fotos sind ca. 1280 px breit; Bilder werden per `object-fit: cover` zugeschnitten.
