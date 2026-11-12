# Paket D: Sicherung des Seminars (Dokumente, Statistiken, Ergebnisse)

Aufwand: **8 ET**. Ziel: Alles, was zu einem Seminar gehört, liegt an einem Ort, ist vollständig, geschützt und nach Abschluss als **Seminarmappe** abrufbar. **Keine Fotos von Teilnehmenden.**

## 1 Ausgangslage
- Es gibt keine Dokumentablage für Seminare. Vorhanden sind: Kostenvoranschläge mit Versionen und Druckansicht (`seminar_quotes`, `seminar_quote_versions`), Termine, Team, Verlauf (`seminar_events`), Anwesenheit und Teilnehmendenlisten nur bei Großveranstaltungen (Modul Events, CSV-Export).
- Dateien: Muster für private Uploads existieren (Mitgliedsdokumente `membership_documents`, Einarbeitungsdokumente `OnboardingDocuments`, Anhänge der Rundschreiben `MailAttachments`).
- Statuspipeline: `Anfrage → In Planung → Terminiert → Bestätigt → Durchgeführt → Abgeschlossen` (Abgesagt jederzeit).

## 2 Entscheidungen

| Frage | Entscheidung |
|---|---|
| Was heißt „Sicherung“? | (1) Ablage aller Dokumente je Seminar, (2) **Schnappschuss** der veränderlichen Daten beim Abschluss, (3) Download als ZIP. Es ist keine Datensicherung der Datenbank (die ist Paket A8). |
| Welche Dateien? | PDF, Word, Excel/CSV, ODT/ODS, ZIP, Text. **Bilddateien sind nicht erlaubt** (JPEG, PNG, GIF, WebP, HEIC), im Dialog mit Begründung. Ausnahme auf Antrag über eine Einstellung (Fotos von Plakaten o. ä. sind nicht vorgesehen). |
| Wer darf? | `seminars.documents`: Koordination, Geschäftsstelle, Vorstand (hochladen, löschen, herunterladen). Teamende sehen nichts. |
| Größe | Einzeldatei 25 MB, je Seminar 500 MB (einstellbar; Hosting hat 100 GB). |
| Aufbewahrung | Abrechnungsunterlagen 10 Jahre, Teamlisten und Auswertungen 3 Jahre, Sonstiges 3 Jahre (je Dokumentart einstellbar). |
| Namen der Teilnehmenden | SuS-Daten gehören nicht ins Seminar. Teilnehmendenzahlen ja, Namenslisten nur als hochgeladenes Dokument, wenn die Schule sie liefert (Hinweis in der Oberfläche, Frist 3 Jahre). |

## 3 Datenmodell

**`seminar_documents`**
| Spalte | Typ | Hinweis |
|---|---|---|
| id | bigint | |
| seminar_id | FK, cascade | |
| kind | string(30) | `contract`, `quote`, `participants`, `evaluation`, `statistics`, `result`, `report`, `invoice`, `other` |
| title | string(200) | |
| original_name | string(200) | bereinigt |
| path | string(255) | `seminars/{id}/{zufällig}.{ext}` |
| mime / size | | |
| is_snapshot | bool | vom Portal erzeugt |
| uploaded_by_user_id | FK null | |
| retention_until | date null | aus Art + Seminarende |
| created_at | | |

**`seminar_closures`** (Abschluss)
| Spalte | Hinweis |
|---|---|
| seminar_id | unique |
| closed_by_user_id, closed_at | |
| checklist | json (Punkt → erledigt/Begründung) |
| snapshot | json: Kennzahlen (Teilnehmende, Termine, Teamgröße, Rückläufe Feedback, Summen) |

## 4 Arbeitspakete

### D1 Ablage (2 ET)
- Klasse `Seminars\Services\SeminarDocuments`: `add(Seminar, UploadedFile, kind, title, user)`, `remove`, `files(seminar)`, `totalBytes`, `retentionFor(kind, seminar)`.
- Prüfung: Erweiterung **und** Inhaltstyp (`finfo`), Bilder abgelehnt, ausführbare Dateien abgelehnt, Name bereinigt, Zufallsname, Größe/Gesamtgröße.
- Controller `SeminarDocumentController` (store, destroy, download), Download mit `Content-Disposition` und `X-Content-Type-Options: nosniff`.
- Seminarseite: Abschnitt **Dokumente** (Tabelle: Art, Titel, Datum, von, Größe, Download, Löschen), Upload-Formular (Art, Titel, Datei) mit Hinweis „Keine Fotos von Teilnehmenden“.
- Protokoll: „Dokument hinzugefügt/entfernt“ mit Art und Seminarnummer (kein Dateiname).
- Tests: Bildablehnung (auch mit falscher Endung), Rechte (Teamende/Schulen 403), Größe, Gesamtgröße, Löschen entfernt die Datei, Download nur für Berechtigte.

### D2 Automatisch erzeugte Dokumente (1,5 ET)
Beim Speichern bzw. auf Knopfdruck erzeugt das Portal Dokumente als **HTML-Druckansicht in Datei** (PDF-Erzeugung auf dem Hosting vermeiden, siehe Risiken):
1. **Kostenvoranschlag** (letzte bestätigte Version) – nutzt die vorhandene Druckansicht.
2. **Terminübersicht** (Tage, Zeiten, Ort).
3. **Teamübersicht** (Namen, Rollen; Kontaktdaten nur nach Einwilligung aus Paket C).
4. **Rückmeldung** (Zusammenfassung der Feedbackformulare, Paket E).
Jedes erzeugte Dokument bekommt `is_snapshot = true` und ist nicht änderbar.
**PDF (Antwort 12):** Jedes automatisch erzeugte Dokument bekommt einen **PDF-Download-Knopf**. Umsetzung mit `dompdf/dompdf` (reines PHP, kein Konsolenzugriff nötig; `gd` oder `imagick` nur für Bilder, Speicher 256 MB). Ein Test in A3 (Zustandsprüfung „PDF-Erzeugung“) erzeugt beim Start ein Probe-PDF auf dem Strato-Hosting. Schlägt es fehl, bleibt die **Druckansicht** als Fallback und der Knopf verschwindet. Schriften: eingebettete Schrift (DejaVu Sans oder die Portalschrift), damit Umlaute stimmen.

### D3 Abschluss mit Checkliste (1,5 ET)
Beim Statuswechsel auf **Abgeschlossen** öffnet sich eine Seite statt des bisherigen Sofortwechsels:
- **Checkliste** (Punkte fest, Reihenfolge fest): Kostenvoranschlag bestätigt · Abrechnung/Rechnung abgelegt · Teamliste vorhanden · Auswertung/Ergebnisse abgelegt · Feedbackformulare geschlossen · Erstattungen und Honorare erledigt (Verweis auf Paket G, sobald da).
- Jeder Punkt: erledigt (automatisch erkannt, wenn Dokument der Art vorhanden) oder **bewusst übersprungen mit Begründung**.
- Beim Bestätigen entsteht der **Schnappschuss** (`seminar_closures.snapshot`) und die automatisch erzeugten Dokumente aus D2. Danach sind Dokumentarten `contract`, `quote`, `invoice` schreibgeschützt (Löschen nur durch Vorstand mit Begründung).
- „Wieder öffnen“ durch Vorstand mit Begründung; Schnappschuss bleibt als Version erhalten.

### D4 Statistiken und Ergebnisse (1 ET)
Kennzahlen im Schnappschuss (nur Zahlen, keine Personendaten):
- Termine (Anzahl, Stunden), Teilnehmende gesamt/je Termin (aus Seminarangabe oder Veranstaltungsmodul), Teamgröße, Kosten (Voranschlag), Rückläufe und Durchschnitte der Feedbackfragen (E), Erstattungen (G).
Seite **Seminarstatistik** (Koordination, Vorstand, Geschäftsstelle): Tabelle über alle abgeschlossenen Seminare mit Filter Jahr, Leistungsart, Schule; Summenzeile; Export CSV (mit Formelschutz wie im Bestand). Jahresbericht (`ReportsView`) übernimmt die Zahlen.

### D5 Seminarmappe als ZIP (1 ET)
- Download „Seminarmappe“ (`ZipArchive`): `Seminar_S-2027-0042/` mit allen Dokumenten nach Art in Unterordnern, `INHALT.txt` (Liste, Datum, Prüfsummen), Schnappschuss als HTML.
- Hosting: ZIP wird in `storage/app/private/tmp` erzeugt und gestreamt; Größengrenze 100 MB (Zeitlimit!); bei größerer Mappe Aufteilung nach Art. Aufräumen der Temp-Dateien über `portal:tick`.
- Protokoll: „Seminarmappe heruntergeladen“.

### D6 Aufbewahrung und Aufräumen (0,5 ET)
Befehl `seminars:prune-documents` löscht Dateien und Datensätze nach `retention_until`, schreibt nur Zahlen ins Protokoll. Systemsteuerung (hoch) und Planer täglich. Übersicht „Läuft bald ab“ auf der Seminarstatistik.

### D7 Tests (im Aufwand enthalten)
`SeminarDocumentsTest`, `SeminarClosureTest`, `SeminarMapTest`, `SeminarPruneTest`:
1. Upload PDF ok, JPEG/PNG/GIF/HEIC abgelehnt (auch umbenannt zu `.pdf`).
2. Rechte (Teamende, Schulkonto, Gast) ohne Zugriff.
3. Schnappschuss enthält Kennzahlen, kein Name von SuS.
4. Abschluss ohne Pflichtpunkte nur mit Begründung; Wiederöffnen nur Vorstand.
5. Schreibschutz der Vertragsdokumente nach Abschluss.
6. ZIP enthält alle Dateien, `INHALT.txt` listet Prüfsummen.
7. Aufräumen löscht nach Frist, nicht davor.
8. Gesamtgröße je Seminar wird eingehalten.

## 5 Datenschutz
- SuS-Fotos: technisch ausgeschlossen (Bildablehnung) und organisatorisch (Hinweis, Anleitung).
- Teamlisten mit Kontaktdaten nur nach Einwilligung; Löschfrist 3 Jahre.
- Zugriff protokolliert (Art + Nummer), Dateien nie öffentlich verlinkbar.

## 6 Abnahme (Teil von M3)
1. Seminar durchspielen: Dokumente hochladen (PDF ok, Foto abgelehnt).
2. Abschluss mit Checkliste, Schnappschuss entsteht.
3. Seminarmappe herunterladen und öffnen, Inhalt vollständig.
4. Statistik zeigt das Seminar mit Kennzahlen.

## 7 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| PDF-Erzeugung auf Hosting zu schwer | HTML-Druckansicht als Standard, PDF nur wenn A1 es zulässt |
| Speicherplatz | Grenzen je Seminar, Anzeige der Gesamtbelegung in der Systemsteuerung |
| ZIP-Zeitlimit | Größengrenze, Aufteilung, Erzeugung beim ersten Abruf mit Hinweis „wird vorbereitet“ (Auftrag über Cron) |
| Falsche Dokumentart | Art nachträglich änderbar bis Abschluss |

## 8 Offene Fragen
1. Fristen aus 4.2 (Überblick) bestätigen lassen (Vorstand/Steuerberatung).
2. Soll die Schule ein Ergebnisdokument (z. B. Schulkonferenz-Protokoll) direkt im Schulkonto hochladen dürfen? Vorschlag: nein in dieser Runde.
3. Entschieden: PDF-Download wenn möglich, sonst Druckansicht (Antwort 12).
