# Changelog

Alle wesentlichen Änderungen am Vereinsportal werden in dieser Datei dokumentiert.

Das Format orientiert sich an Keep a Changelog. Releases verwenden Semantic Versioning.

## [Unreleased]

### Added

- Briefvorlagen aus Word mit Platzhaltern, Wenn-/Ohne-Blöcken, Platzhalterliste und sieben Beispielvorlagen (Docs 77, 78)
- Vorstandssitzungen: Planung, Tagesordnung, Anwesenheit, Protokoll, Anhänge, nummerierte Beschlüsse, Kalender-Abo (.ics), abschaltbare Einladungen, externe Gäste mit automatischer Löschung (Docs 79, 80)
- Allgemeines Aufgabensystem mit Projekten, eigenen Spalten, Kanban- und Listenansicht, Labels, Unteraufgaben, Anhängen, Vorlagen, Kommentaren und persönlichen E-Mail-Erinnerungen (Doc 81)
- Kanban-Tafel als Baustein des Designsystems (`kanban`, `kanban-column`, `kanban-card`)
- Überlaufmenü („…“) im Kopfbereich bei mehr als acht Einträgen
- Lückenschließung Pakete A–K: Feedback, SSO/OIDC, Anträge, Verteiler, Honorarvertrag, Release-Skripte (Docs 67, 75)
- Baseline für die statische Analyse (`phpstan-baseline.neon`) und Ausnahmeliste für `npm audit` (`scripts/npm-audit-allowlist.json`)
- Web Content Library und versioniertes Designsystem als Entwicklungsreferenz
- Repository-Standards für Branching, CODEOWNERS und Zusammenarbeit
- gehärtete CI mit PHP-, MySQL-, Node-, Pest-, Pint- und Build-Prüfungen
- automatisierte Composer-, npm-, Dependency-, Secret- und CodeQL-Sicherheitschecks
- gruppierte Dependabot-Updates und Release-Notes-Konfiguration

### Changed

- „Ihre Aufgaben“ von der öffentlichen Startseite ins Konto und in die Bereichsübersichten verschoben; neuer Bereich „Aufgaben“
- Vorstandsaufgaben ziehen per Migration ins Projekt „Vorstand“ um (Tabellen `board_tasks` und `board_task_user` entfallen; vorher Datenbank sichern)
- CI prüft die Design-Bibliothek mit `design:check`
- Neue Abhängigkeit `phpoffice/phpword` (braucht `ext-gd`)
- Entwicklungsmodell auf kurze Pull-Request-Branches direkt nach `main` vereinheitlicht
- GitHub Actions auf feste Commit-SHAs gepinnt
- PR- und Issue-Templates an aktuelle QA-, Security- und Datenschutzregeln angepasst

### Security

- `phpseclib/phpseclib` auf 3.0.58 (PKSA-h7cz-q7zq-mdkt)
- `tinymce` GHSA-mf2p-h6hf-fcwm begründet freigegeben (Plugin `media` wird nicht geladen)
- minimale Workflow-Berechtigungen und nicht persistierte Checkout-Credentials dokumentiert
- öffentliche Security-Issues klar von vertraulichen Vulnerability Reports getrennt
