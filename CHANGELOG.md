# Changelog

Alle wesentlichen Änderungen am Vereinsportal werden in dieser Datei dokumentiert.

Das Format orientiert sich an Keep a Changelog. Releases verwenden Semantic Versioning.

## [Unreleased]

### Added

- Komponenten-Werkstatt unter `/design/komponenten` mit HTML-Struktur der v2-Komponenten und Startgestaltung in `resources/css/vdbs/v2/`
- Web Content Library und versioniertes Designsystem als Entwicklungsreferenz
- Repository-Standards für Branching, CODEOWNERS und Zusammenarbeit
- gehärtete CI mit PHP-, MySQL-, Node-, Pest-, Pint- und Build-Prüfungen
- automatisierte Composer-, npm-, Dependency-, Secret- und CodeQL-Sicherheitschecks
- gruppierte Dependabot-Updates und Release-Notes-Konfiguration

### Changed

- Designsystem v2 (Grundlagen): Tokens an Markenpaket v7.2 angeglichen (Grün, Lila, Link, Fokus, Fehler), neue Rollen und Gruppenfarben, Lato als Basisschrift; Dokumentation in `docs/33_DESIGN_SYSTEM_V2_GRUNDLAGEN.md`
- Designsystem v2 im Portal durchgesetzt: Ocean Mist und Zusatzfarben entfernt (Lila statt Ocean), Hauptbutton-Hover und Footer-Leiste in Markenfarben, Tokens statt fester Werte
- Fehlerseiten: Bildfläche im Format 3:2, weiterführende Links auf jeder Fehlerseite (`ErrorPageLinks`), Bilder für 429 und 503 (Wartungsarbeiten)
- Komponente „Bild-Text“ (`vdbs-bildtext`, Bild links, Text und Buttons rechts) und Bild-Variante der Fehlerseite (`image`)
- Fehlerseiten verwenden den normalen Portal-Header und -Footer (abgesichert über `ErrorPageChrome`, mit Rückfall auf die reduzierte Hülle)
- Entwicklungsmodell auf kurze Pull-Request-Branches direkt nach `main` vereinheitlicht
- GitHub Actions auf feste Commit-SHAs gepinnt
- PR- und Issue-Templates an aktuelle QA-, Security- und Datenschutzregeln angepasst

### Security

- minimale Workflow-Berechtigungen und nicht persistierte Checkout-Credentials dokumentiert
- öffentliche Security-Issues klar von vertraulichen Vulnerability Reports getrennt
