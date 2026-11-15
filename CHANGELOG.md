# Changelog

Alle wesentlichen Änderungen am Vereinsportal werden in dieser Datei dokumentiert.

Das Format orientiert sich an Keep a Changelog. Releases verwenden Semantic Versioning.

## [Unreleased]

### Added

- Anbindungen: neuer Scope `portal_profile` gibt Funktion, Kurzvorstellung und Profilbild an angebundene Systeme (z. B. Moodle) weiter, nur soweit für Angemeldete freigegeben (Doc 72)
- Ticket-System: Tickets anlegen, im Ticket schreiben (Chat mit internen Notizen), Zuweisung an Bearbeitende, Status und Priorität, Verlauf je Ticket und Protokoll im Portal, Warteschlange mit Mehrfachauswahl (Doc 84); die bisherigen „Meine Tickets“ (Veranstaltungen) heißen „Meine Anmeldungen“
- Schnellzugriffe: je Rolle drei Links mit Wichtigkeit, die wichtigsten Links aller Rollen im Profilmenü (Doc 84)
- Kontaktformular: Angemeldete geben ihre Daten (vorbelegt aus dem Konto) in Schritt 3 an
- Formatierte Texte (Editor) bei FAQ-Antworten sowie Beschreibungen von Aufgaben und Aufgabenvorlagen
- Kontaktformular: Schritt 2 (Angaben zur Person) und Schritt 3 (Anfrage oder weitere Angaben) sind getrennte Seiten
- Aufgaben: neue Aufgaben auf eigener Seite, kompakte Such- und Filterleiste, Kanban-Spalten mittig und im neuen Design
- Kontaktformular als geführter Ablauf (Thema wählen, Angaben zur Person, Anfrage oder nur Angaben; Themen ohne Formular führen zur FAQ-Seite, keine Infoseiten unter /kontakt) mit im Portal pflegbaren Themen, FAQ-Seiten, Formularen und eigenen Feldern (Doc 82)
- FAQ als eigenes Modul: große FAQ-Seite `/faq` mit Suche, eigene Verwaltung (Geschäftsstelle › FAQ) mit Kategorien und Fragen, vom Kontaktformular getrennt; Entwurf der Fragen und Antworten als Ausgangsbestand (Doc 83); Kategorien lassen sich auf Angemeldete oder bestimmte Rollen beschränken (z. B. Honorare nur für Teamende und Verwaltung)
- Designsystem: Auswahlkästchen für Tabellenzeilen (`x-vdbs.data.table-check`) mit Kopfkästchen „Alle auswählen“; alle Tabellen mit Mehrfachauswahl nutzen es
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

- Profilmenü neu gegliedert (Kopf mit Name und Rolle; Mein Profil, Kontoeinstellungen, Meine Tickets; Kontakt, FAQ, Hilfe; Abmelden), neue Seite „Meine Tickets“ im Konto; Startmenü zeigt flach Über das Portal, Zugang zum Portal, FAQ, Kontakt
- Aufgaben sind kein eigener Bereich mehr: Menüpunkt „Aufgaben“ in den Bereichen mit den Projekten des Bereichs (neues Feld „Bereich“ am Projekt), „Meine Aufgaben“ im Konto, allgemeine Verwaltung der Projekte (mit Mehrfachauswahl) in der Geschäftsstelle
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
