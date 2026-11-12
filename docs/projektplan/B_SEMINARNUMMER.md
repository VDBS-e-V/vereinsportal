# Paket B: Seminarnummer und Stammdaten

Aufwand: **3 ET**. Ziel: Jedes Seminar bekommt beim Anlegen eine **unveränderliche, lesbare Nummer** und eine vollständige Stammdatenerfassung.

## 1 Ausgangslage
- `seminars`: `title`, `type`, `partner_id`, `status`, `grade_level`, `expected_participants`, `team_needed`, `application_deadline`, `team_competences`, `notes`, Ersteller/Bearbeiter. Termine liegen in `seminar_sessions` (Beginn, Ende, Ort, Hinweis), Verlauf in `seminar_events`.
- Seminare sind nur über die technische ID adressierbar (`/seminare/{id}`).
- Kostenvoranschläge tragen eine eigene Versionsnummer, keine Seminarnummer.

## 2 Entscheidungen

| Frage | Vorschlag | Begründung |
|---|---|---|
| Format | `S-JJJJ-NNNN`, z. B. `S-2027-0042` | Lesbar am Telefon, Jahr erkennbar, vier Stellen reichen für 9 999 Seminare je Jahr. |
| Jahr | **Anlegedatum** des Seminars, nicht Seminartermin | Der Termin steht beim Anlegen oft noch nicht fest. |
| Zähler | je Jahr bei 1 beginnend, **lückenlos pro Anlegen**, gelöschte Nummern werden nicht neu vergeben | Nachvollziehbarkeit; Löschen ist ohnehin nur bei Anfragen und abgesagten Seminaren erlaubt. |
| Änderbar? | Nein. Korrektur nur durch die Administration über die Systemsteuerung (nicht in der Oberfläche) | Nummern landen in Mails, Belegen und Dokumenten. |
| Anzeige | in Listen, Kopf der Seminarseite, Kostenvoranschlag, Mails an Schule und Team, Zustellprotokoll nicht (kein Personenbezug nötig) | |

Wenn Sie ein anderes Format wollen (z. B. mit Leistungsart: `SVT-2027-042`), ist das nur Konfiguration (siehe 3.2).

## 3 Arbeitspakete

### B1 Datenmodell und Vergabe (1 ET)
**Migration** `add_number_to_seminars`:
- `seminars.number` string(20) nullable, **unique**, Index.
- Neue Tabelle `seminar_counters`: `year` (smallint, primary), `last` (unsigned int).

**Klasse** `Seminars\Services\SeminarNumbers`:
```php
public function next(?Carbon $at = null): string   // reserviert und liefert z. B. "S-2027-0042"
```
Vergabe in einer Transaktion: `SELECT … FOR UPDATE` auf die Zeile des Jahres; fehlt sie, wird sie angelegt (`INSERT … ON DUPLICATE KEY UPDATE` für Parallelität). Format aus `config('portal.seminars.number_format')` (Standard `S-{Y}-{N:4}`).

**Aufruf:** `SeminarAdministration::create()` vergibt die Nummer; Schulanfragen (`SchoolRequests`) ebenso. Ein `creating`-Ereignis im Modell dient als Absicherung (nie ein Seminar ohne Nummer).

**Nachtrag für vorhandene Seminare:** Teil der Migration oder eigener Befehl `seminars:number-existing`. Reihenfolge nach `created_at`, getrennt je Jahr. Idempotent: setzt nur dort, wo `number` leer ist. Läuft über die Systemsteuerung (neue Aufgabe `seminarnummern-nachtragen`, Risiko mittel).

### B2 Anzeige und Suche (0,75 ET)
- Seminarliste: neue Spalte „Nr.“ (erste Spalte, sortierbar), Suchfeld findet auch Nummer und Teilnummer (`2027-0042`, `42`).
- Seminarseite: Nummer im Seitenkopf als Badge neben dem Status.
- Kostenvoranschlag: Kopfzeile und Druckansicht „Seminar S-2027-0042“; E-Mails an Schule/Team: Platzhalter `{seminar_nr}` in den Mailvorlagen für Teamende (`MailPlaceholders::TEAM`) und Betreffzeilen der Systemmails.
- Protokolleinträge und Aufgabenübersicht zeigen die Nummer statt der ID (Protokoll bleibt ohne Titel/Schulnamen, die Nummer ist unkritisch).
- Dokumente (Paket D), Feedbackformulare (E), Erstattungsanträge (G) verweisen über die Nummer.

### B3 Stammdaten (0,75 ET)
Neue Felder mit Begründung (zur Bestätigung):

| Feld | Typ | Pflicht | Zweck |
|---|---|---|---|
| Ansprechperson Schule | Verweis auf `partner_contacts` | nein | Wer wird bei Rückfragen angerufen |
| Anzahl Klassen/Gruppen | Zahl | nein | Planung Teamgröße |
| Räume/Hinweise zum Ort | Text 500 | nein | Anreise, Hausmeister |
| Anmeldedatum der Anfrage | Datum | ja (automatisch) | Fristen |
| Kostenstelle | Text 40 | nein | Buchhaltung (Paket G) |
| Verantwortliche Koordination | Verweis auf Konto | nein | Zuständigkeit |

Die Felder erscheinen in `form.blade.php` im Abschnitt „Stammdaten“; Pflichtfelder werden nicht strenger, bestehende Seminare bleiben gültig.

### B4 Tests und Doku (0,5 ET)
Tests (`SeminarNumbersTest`):
1. Erste Nummer des Jahres endet auf `0001`, die nächsten zählen lückenlos hoch.
2. Jahreswechsel startet neu (mit `Carbon::setTestNow`).
3. Gleichzeitige Vergabe (zwei Anlegevorgänge in einem Test hintereinander mit gleicher Zeit) liefert verschiedene Nummern.
4. Seminar ohne Nummer ist nicht möglich (`creating`).
5. Nummer ändert sich beim Bearbeiten nicht.
6. Nachtragsbefehl nummeriert alte Seminare nach `created_at`, lässt vorhandene Nummern unberührt.
7. Liste durchsuchbar nach `2027-0042` und `42`.
8. Schulanfrage vergibt eine Nummer.

Doku: `docs/48_SEMINARE.md` Abschnitt „Seminarnummer“; Anleitung „Seminar finden“ in einfacher Sprache.

## 4 Datenschutz und Betrieb
Die Nummer enthält keine personenbezogenen Daten. Kein neuer Aufräumbefehl. Einmalig auf dem Livesystem: Nachtragsaufgabe nach dem Update ausführen.

## 5 Abnahme
- Seminar anlegen → Nummer sichtbar in Liste, Kopf, Kostenvoranschlag.
- Suche nach der Nummer findet das Seminar.
- Bestehende Seminare tragen nach der Aufgabe Nummern.

## 6 Offene Fragen
1. Format `S-JJJJ-NNNN` bestätigen oder anpassen.
2. Soll die Nummer bei Großveranstaltungen (Modul Veranstaltungen) ebenfalls vergeben werden (`V-2027-0007`)? Vorschlag: ja, gleicher Dienst, anderes Präfix.
3. Kostenstelle: gibt es feste Kostenstellen im Verein?
