# 70 Sicherung des Seminars (Paket D, Teil 1)

Stand: Teil 1 von Paket D (Ablage, Abschluss mit Checkliste, Seminarmappe, Aufbewahrung). **Noch nicht auf dem echten System erprobt.** Teil 2 bringt automatisch erzeugte Dokumente mit PDF und die Seminarstatistik.

## Dokumente
Auf der Seminarseite (Abschnitt „Dokumente“) legen Koordination, Geschäftsstelle und Vorstand Dateien ab (Berechtigung `seminars.documents`; Teamende, Schulen und Bildungsreferenten sehen nichts). Erlaubt sind PDF, Word, Excel, CSV, Text, OpenDocument und ZIP, bis 25 MB je Datei und 500 MB je Seminar (`SEMINAR_DOCUMENT_MAX_KB`, `SEMINAR_DOCUMENTS_MAX_TOTAL_KB`). **Bilder werden nicht angenommen** (JPEG, PNG, GIF, HEIC …), auch nicht als „.pdf“ getarnt: geprüft werden Endung und Inhalt. So landen keine Fotos von Teilnehmenden im Portal.
- Mehrere Dateien auf einmal hochladen; eine abgelehnte Datei hält die übrigen nicht auf.
- Löschen per Mehrfachauswahl (mit „alle auswählen“).
- Die Dateien liegen privat unter `storage/app/private/seminars/<ID>/` mit Zufallsnamen und werden nur über das Portal ausgeliefert.
- Im Protokoll stehen Art und Seminarnummer, nie der Dateiname.

## Abschluss mit Checkliste
Der Statuswechsel auf „Abgeschlossen“ führt auf eine eigene Seite. Die Checkliste ist fest: Kostenvoranschlag, Abrechnung oder Rechnung, Team, Auswertung oder Ergebnis (automatisch erkannt) und „Honorare und Erstattungen erledigt“ (von Hand). Offene Punkte werden bestätigt oder mit Begründung übersprungen. Beim Abschluss entsteht der **Schnappschuss** (nur Zahlen: Termine, Stunden, Teamgröße, Teilnehmende, Endsumme, Zahl der Dokumente je Art; keine Namen).
- Danach sind **Vertrag, Kostenvoranschlag und Rechnung geschützt**: löschen oder ergänzen kann nur der Vorstand, das Löschen nur mit Begründung.
- **Wieder öffnen** kann nur der Vorstand (`seminars.reopen`) mit Begründung. Der alte Schnappschuss bleibt als Version erhalten.

## Seminarmappe
„Seminarmappe herunterladen (ZIP)“ liefert alle Dokumente nach Art in Unterordnern, `INHALT.txt` mit Größe, SHA-256 und Datum sowie den Schnappschuss als HTML. Bis 100 MB (`SEMINAR_FOLDER_MAX_MB`); die Datei entsteht kurz in `storage/app/private/tmp` und wird nach dem Senden gelöscht. Voraussetzung ist die PHP-Erweiterung `zip` (auf Strato vorhanden).

## Aufbewahrung
`php artisan seminars:prune-documents` (täglich und als Systemaufgabe „Seminardokumente aufräumen“) löscht Dokumente nach der Frist: Verträge, Kostenvoranschläge und Rechnungen 10 Jahre, alles andere 3 Jahre nach dem letzten Termin (`config/portal.php` › `seminars.documents.retention_years`). **Die Fristen bitte mit Vorstand oder Steuerberatung bestätigen.**

## Noch offen (Teil 2)
Automatisch erzeugte Dokumente (Terminübersicht, Teamübersicht, Kostenvoranschlag) mit PDF-Download, Seminarstatistik über alle abgeschlossenen Seminare mit CSV-Export, Übernahme in den Jahresbericht, Anzeige „läuft bald ab“.
