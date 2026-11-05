# Feedback (Paket E, Teil 1)

Digitale Rückmeldebögen für Schülerinnen und Schüler, Lehrkräfte und Teamende.

## Ablauf
1. **Formulare** (Koordination → Feedbackformulare): drei Standardformulare, Fragen bearbeiten, Reihenfolge ändern (Zahl „Platz“), Fragen entfernen oder hinzufügen, Vorschau, Zurücksetzen. Jede Speicherung erzeugt eine neue Fassung; Links halten die Fassung fest, mit der sie angelegt wurden.
2. **Links** (Seminarseite → Feedback): pro Gruppe ein Link, mehrere Gruppen gleichzeitig, Zeitraum (Standard: erster Termin bis 14 Tage nach dem letzten Termin), Obergrenze (Klassen: 1,5 × erwartete Teilnehmende), Mindestzahl für Auswertungen (Standard 5). Mehrfachauswahl zum Beenden. Aushang zum Drucken.
3. **Antwortseite** `/feedback/{token}`: öffentlich, ohne Konto.

## Datenschutz
- Antworten tragen **keine Kennung**: gespeichert werden nur Link, Tag der Abgabe, Antworten und bei Teamenden ein freiwilliger Name. Keine IP, kein Gerät, keine Sitzung.
- Der Linkcode ist als Hash (Suche) und verschlüsselt (Anzeige für die Koordination) abgelegt.
- Rechte: `feedback.manage` (Formulare, Links) und `feedback.view` (Antworten lesen, Teil 2) nur für die Koordination.

## Konfiguration
`FEEDBACK_MIN_RESPONSES` (5), `FEEDBACK_RETENTION_MONTHS` (12).

## Noch offen (Teil 2)
Auswertung, Anonymisieren/Archivieren, Aufräumbefehl, Erinnerungen, Platzhalter in Schreiben, Kennzahlen im Seminarabschluss.

## Teil 2: Auswertung, Anonymisieren, Platzhalter

- **Auswertung** (Koordination → Feedback-Auswertung, Recht `feedback.view`): je Seminar (`Auswertung ansehen` auf der Seminarseite) mit Durchschnitt, Verteilung, Ja/Nein, Auswahl und Freitexten in **zufälliger Reihenfolge**; im Vergleich mit Mehrfachfiltern Jahr, Leistung, Schule und Gruppe, Rücklauf gegen erwartete Teilnehmende, CSV-Export (ohne Texte).
- **Mindestzahl:** Unter der Mindestzahl je Link (Standard 5) werden keine Ergebnisse gezeigt.
- **Anonymisieren:** nach `FEEDBACK_RETENTION_MONTHS` (12) nach Ende des Links täglich automatisch (`feedback:prune`, auch als Systemaufgabe „Feedback anonymisieren“), oder von Hand je Seminar (Mehrfachauswahl, Recht `feedback.manage`). Bei genug Antworten bleiben die Zahlen im Archiv (`feedback_archives`), Einzelantworten, Freitexte und Namen werden gelöscht. Bei zu wenigen Antworten wird nur gelöscht.
- **Platzhalter** in Schreiben an Teamende: `{feedback_link_lehrkraft}` und `{feedback_link_team}` (leer, wenn kein offener Link besteht).

Noch nicht gebaut: Erinnerungen an Lehrkräfte/Teamende, Kennzahlen im Seminarabschluss, Umbenennen des Schulfeedbacks in „Nachricht an die Koordination“ (E6).

## Ergänzungen
- **Erinnerung** (`feedback:remind`, täglich): Eine Woche nach dem letzten Termin (`FEEDBACK_REMIND_AFTER_DAYS`) erhalten Lehrkräfte (Hauptansprechperson der Schule, sonst alle Ansprechpersonen mit E-Mail) eine Erinnerung, wenn noch niemand geantwortet hat; das bestätigte Team, wenn weniger als die Hälfte geantwortet hat. Einmal je Link, die Mail enthält nur Seminar und Link.
- **Seminarabschluss:** Der Schnappschuss enthält `feedback` (Antworten und Durchschnitt je Gruppe), aber nur aus **anonymisierten** Links. Beim Anonymisieren wird der geltende Abschluss ergänzt.
- **Schulen:** Das Freitextfeld heißt jetzt „Nachricht an die Koordination“. Solange ein Lehrkräfte-Link offen ist, steht der Link zum Fragebogen auf der Seite der Schule.
