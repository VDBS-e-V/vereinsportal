# Stand der Lückenschließung und offene Punkte

Stand: alle Pakete des Projektplans (`67_PROJEKTPLAN_LUECKEN.md`) sind umgesetzt, bis auf die unten genannten Reste. Letzter Testlauf auf dem Entwicklungsrechner: **1205 bestanden, 4 übersprungen, 0 Fehler**.

## Umgesetzt (Auswahl, mit Dokumentation)
| Paket | Inhalt | Doku |
|---|---|---|
| E–F | Feedback (anonym, Versionen, Auswertung, Erinnerungen), SSO/OIDC-Anbindung | 71, 72 |
| G | Anträge (Auslagen, Fahrtkosten, Bestellungen) je Bereich, Freigaberegeln, Exporte, Kostenstelle am Seminar | 74 |
| H | Verteiler mit Filtern, Rechte-Matrix, Grundbestand an Vorlagen und Bausteinen, Rundschreiben der Koordination | 63 |
| I | Rollen Mitarbeitende, Geschäftsführung, Kassenführung; lokales Testen | 73, 43, 40 |
| K | Honorarvertrag: Vertragstext (versioniert), Vertragsdaten, Druckansicht, optional PDF, Scan, Abschlussliste, Erinnerung | 70 |

## Offen und braucht eine Entscheidung oder Prüfung durch den Verein
1. **Vertragstext:** Version 1 ist ein Entwurf nach der Klauselliste. Der Wortlaut von `Vertragsvorlage_-1.pdf` muss als neue Version eingespielt und rechtlich geprüft werden (Scheinselbstständigkeit).
2. **Reisekostensatz (Q12):** Der Vertrag verweist auf das Bundesreisekostengesetz, die Fahrtkostenabrechnung rechnet 30 Cent je km (Einstellung änderbar). Mit der Steuerberatung klären.
3. **Datenschutzerklärung:** Die Abschnitte sind Entwürfe; Datenschutzbeauftragte prüfen. Aufbewahrungsfristen (10/3 Jahre, 12 Monate Feedback, 30 Tage IBAN) sind Annahmen.
4. **Textentwürfe:** Der Grundbestand an Mailvorlagen und Bausteinen ist ein Entwurf zur Freigabe.
5. **Abnahme mit echten Systemen:** Nextcloud/Moodle-Anmeldung über OIDC (bisher nur durch Tests belegt), Verträge und Anträge mit echten Daten.
6. **Vor dem Go-live:** Dependabot-Hinweise und `npm audit` prüfen, Probelauf auf dem Strato-Hosting.

## Optional und nicht umgesetzt
(Bestellung → Erstattung, Gruppe Importierte Kontakte, QR-Code auf dem Feedbackplakat sind umgesetzt.)
- PDF der Verträge ohne dompdf (Paket installieren: `composer require dompdf/dompdf --ignore-platform-req=ext-sodium`).
- Arbeitszeiterfassung (Paket J) ist nur ein Ausblick.
