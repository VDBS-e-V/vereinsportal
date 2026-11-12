# Stand der Lückenschließung und offene Punkte

Stand: alle Pakete des Projektplans (`67_PROJEKTPLAN_LUECKEN.md`) sind umgesetzt, bis auf die unten genannten Reste. Letzter vollständiger Testlauf auf dem Entwicklungsrechner: **1205 bestanden, 4 übersprungen, 0 Fehler**. Seitdem sind Vorstandssitzungen, Aufgabensystem, Kanban-Baustein und Erweiterungen der Briefvorlagen hinzugekommen (siehe unten); für diese Teile liegt **noch kein Testlauf** vor, sie sind nur syntaxgeprüft. Nächster Schritt: `composer install`, `php artisan portal:update`, `npm ci && npm run build`, den ganzen Pest-Lauf und die manuellen Prüfungen unten.

## Umgesetzt (Auswahl, mit Dokumentation)
| Paket | Inhalt | Doku |
|---|---|---|
| E–F | Feedback (anonym, Versionen, Auswertung, Erinnerungen), SSO/OIDC-Anbindung | 71, 72 |
| G | Anträge (Auslagen, Fahrtkosten, Bestellungen) je Bereich, Freigaberegeln, Exporte, Kostenstelle am Seminar | 74 |
| H | Verteiler mit Filtern, Rechte-Matrix, Grundbestand an Vorlagen und Bausteinen, Rundschreiben der Koordination | 63 |
| I | Rollen Mitarbeitende, Geschäftsführung, Kassenführung; lokales Testen | 73, 43, 40 |
| L | Briefvorlagen aus Word mit Platzhaltern, Erzeugen am Seminar | 77 |
| L+ | Briefvorlagen erweitert: Wenn-/Ohne-Blöcke, ausführliche Platzhalterliste mit Beispielen, Liste als Word-Datei | 77, 78 |
| M | Vorstandssitzungen: Planung, Tagesordnung, Anwesenheit, Protokoll mit Genehmigung, Anhänge, Beschlüsse (nummeriert je Jahr), geheimer Kalender-Abo-Feed (.ics), abschaltbare Einladungen per E-Mail, externe Gäste mit automatischer Löschung | 79, 80 |
| N | Aufgabensystem: Projekte mit eigenen Spalten, Zuständige, Kanban und Liste, Labels, Unteraufgaben, Anhänge, Vorlagen, Kommentare; Vorstandsaufgaben im Projekt „Vorstand“; E-Mail-Erinnerungen als persönliche Wahl (standardmäßig aus) | 81 |
| O | Designsystem: Kanban-Tafel als Baustein im Paket „Daten“ (`kanban`, `kanban-column`, `kanban-card`, `resources/js/kanban.js`) | 59 |
| K | Honorarvertrag: Vertragstext (versioniert), Vertragsdaten, Druckansicht, optional PDF, Scan, Abschlussliste, Erinnerung | 70 |

## Offen und braucht eine Entscheidung oder Prüfung durch den Verein
1. **Vertragstext:** Der Wortlaut der Vertragsvorlage ist eingespielt. Rechtlich prüfen lassen (Scheinselbstständigkeit).
2. **Reisekostensatz:** Umgestellt auf das Bundesreisekostengesetz (20 Cent je km, höchstens 130 Euro je Fahrt, Einstellung änderbar).
3. **Datenschutzerklärung:** Die Abschnitte sind Entwürfe; Datenschutzbeauftragte prüfen. Aufbewahrungsfristen (10/3 Jahre, 12 Monate Feedback, 30 Tage IBAN) sind Annahmen.
4. **Textentwürfe:** Der Grundbestand an Mailvorlagen und Bausteinen ist ein Entwurf zur Freigabe.
5. **Abnahme mit echten Systemen:** Nextcloud/Moodle-Anmeldung über OIDC (bisher nur durch Tests belegt), Verträge und Anträge mit echten Daten.
6. **Datenschutz Vorstand und Aufgaben:** Interessenabwägung für externe Gäste (Art. 6 Abs. 1 lit. f), Aufbewahrung von Protokollen mit Namen Dritter, Eintrag im Verzeichnis der Verarbeitungstätigkeiten (Entwurf in `80_DATENSCHUTZ_VORSTANDSSITZUNGEN.md`). Bereits gespeicherte Datenschutzseiten brauchen die neuen Abschnitte „Vorstandssitzungen und Gäste“ und „Aufgabenverwaltung“ von Hand.
7. **Manuelle Prüfungen, die nur mit echten Systemen gehen:** Briefvorlagen mit dem echten Briefbogen in Word (Kopf-/Fußzeile, Tabellen, Textfelder); Kalender-Abo und `.ics`-Anhang in Outlook/Apple/Thunderbird; Zustellung der Einladungs- und Aufgabenmails; Kanban per Ziehen und Ablegen im Browser; Strato-Probelauf (`76_STRATO_PROBELAUF_CHECKLISTE.md`).
8. **Vor dem Go-live:** Dependabot-Hinweise und `npm audit` prüfen, Probelauf auf dem Strato-Hosting.

## Optional und nicht umgesetzt
(Bestellung → Erstattung, Gruppe Importierte Kontakte, QR-Code auf dem Feedbackplakat sind umgesetzt.)
- PDF-Optik der Verträge und Seminardokumente prüfen (dompdf ist jetzt in `composer.json`).
- Arbeitszeiterfassung (Paket J) ist nur ein Ausblick.
- Besprochen, aber nicht umgesetzt: Schreiben direkt aus Schul- und Personenseiten und Serienbriefe; erzeugte Briefe als Seminardokument ablegen; Absage-Mail bei abgesagter Vorstandssitzung; Aufgaben auf der Startseite („Ihre Aufgaben“); wiederkehrende Aufgaben; Zuweisung von Aufgaben an Personen außerhalb der Projekte.

## Sicherheitsprüfung der Abhängigkeiten (Stand 08.11.2026)
- `composer audit`: 15 Hinweise in 8 Paketen (laravel/framework, league/commonmark, livewire, symfony/html-sanitizer, http-foundation, http-kernel, mime, routing) durch Patch-Updates innerhalb der erlaubten Versionen behoben (`composer.lock`). Danach: keine Hinweise.
- `npm audit`: 5 von 6 Hinweisen behoben (follow-redirects, shell-quote/concurrently, source-map-js, vite). Offen: **tinymce 7.9.3** (XSS über das Plugin `media`). Behoben erst in TinyMCE 8 (Hauptversion mit anderer Lizenz). Das Portal lädt nur die Plugins `lists link autolink`, das betroffene Plugin ist **nicht aktiv**; bewusst nicht aktualisiert. Beim nächsten Wechsel auf TinyMCE 8 Lizenz und Texteditor-Tests prüfen.
- Seit dem Aufgabensystem gibt es einen weiteren Bereich „Aufgaben“ (zwölf Bereiche, `43_BEREICHE.md`).
- Nach dem Einspielen: `composer install`, `npm ci`, `npm run build`, `php artisan portal:update`, Tests.
