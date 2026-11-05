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
