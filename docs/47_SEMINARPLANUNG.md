# 47 Seminarplanung (Konzept, Entwurf)

Stand: Entwurf zur Abstimmung. Es ist noch nichts gebaut. Grundlage sind die Antworten des Vorstands/der Koordination und die
Excel-Tabelle „Preisberechnungstabelle VDBS“. Offene Punkte stehen am Ende.

## 1 Ziel

Die Koordination plant Seminare im Portal: von der Anfrage einer Schule über Kostenvoranschlag, Termine und Teamende bis zur Durchführung und
Nachbereitung. Für Großveranstaltungen gibt es zusätzlich ein Anmeldesystem für Schülerinnen und Schüler (SuS).

## 2 Begriffe

| Begriff | Bedeutung |
|---|---|
| **Seminar** | Eine Leistung des Vereins mit Titel, Leistungsart, Status, mehreren Einheiten, Teamenden und Kosten |
| **Einheit** | Ein Termin eines Seminars (Datum, Beginn, Ende, Ort) |
| **Schulseminar** | Findet bei einer Partnerschule statt (Verknüpfung mit dem Modul Schulen) |
| **Großveranstaltung** | Offenes Seminar für SuS mehrerer Schulen, mit Anmeldesystem; hat mit Mitgliedschaft nichts zu tun |
| **Leistungsart** | Vorlage mit Preislogik und Standardwerten (aus der Excel-Tabelle: SV-Tage, SV-Fahrt, SV-Coaching/Kurze Tagestreffen, SV-Wahl) |
| **Thema/Format** | Wiederverwendbare Beschreibung (Titel, Text, Dauer, Zielgruppe) |
| **Teamende** | Personen mit der Rolle Teamende, die Seminare durchführen |
| **Kostenvoranschlag** | Berechneter, eingefrorener Preis für ein Seminar (kein Beleg, keine Rechnung) |

## 3 Ablauf (Status-Pipeline)

`Anfrage → In Planung → Terminiert → Bestätigt → Durchgeführt → Abgeschlossen`, jederzeit `Abgesagt`.

- Jeder Wechsel steht im Verlauf des Seminars (wer, wann, Anmerkung), wie bei Mitgliedschaften (`MembershipHistory`).
- **Anfrage:** angelegt von der Koordination (Anfrage kam per Mail/Telefon), später ggf. über ein öffentliches Formular der Schule.
- **In Planung:** Leistungsart, Einheiten, Bedarf an Teamenden, Kostenvoranschlag in Arbeit.
- **Terminiert:** Einheiten stehen fest; Teamende können sich bewerben.
- **Bestätigt:** Schule hat den Kostenvoranschlag/Termin bestätigt, Teamende sind zugewiesen.
- **Durchgeführt:** alle Einheiten haben stattgefunden (Anwesenheit erfasst, bei Großveranstaltungen).
- **Abgeschlossen:** Nachbereitung fertig (Kurzbericht, Dokumente); Daten laufen in die Aufbewahrung.

## 4 Rollen und Seiten

Alle Seiten gehören einem der bestehenden Bereiche (Regel aus `docs/43_BEREICHE.md`).

| Bereich | Seite | Wer | Capability (neu) |
|---|---|---|---|
| Koordination | Seminare (Liste, Detail, Anlegen/Bearbeiten, Verlauf) | Koordination, Geschäftsstelle, Vorstand | `seminars.view`, `seminars.manage` |
| Koordination | Themen und Preise (Katalog, Preisliste) | Koordination, Vorstand | `seminar_catalog.manage` |
| Teamende | Offene Seminare (sehen, bewerben, zurückziehen), Meine Seminare | Teamende | `seminars.apply` |
| Koordination | Anmeldungen (je Großveranstaltung: Liste, Upload, Anwesenheit) | Koordination | `seminars.manage` |
| Start (öffentlich) | Anmeldeseite je Großveranstaltung (`/seminare/{kennung}/anmeldung`) | alle | keine |

Der Vorstand darf wie überall alles. Bildungsreferenten sind in dieser Stufe nicht beteiligt.

## 5 Daten (Vorschlag)

Modul `app/Modules/Seminars`.

- `seminars`: id, public_id, kind (`school`|`event`), title, topic_id, service_type_id, partner_id (nullable, Pflicht bei Schulseminar), status, notes, expected_participants, grade_level, team_size_needed, created_by/updated_by.
- `seminar_sessions` (Einheiten): seminar_id, starts_at, ends_at, location, notes.
- `seminar_topics`: Katalog (title, description, default_duration, target_group).
- `seminar_applications` (Bewerbung Teamende): seminar_id, person_id/user_id, status (`applied`|`confirmed`|`declined`|`withdrawn`), decided_by, note.
- `seminar_events` (Verlauf): wie `membership_events`.
- `seminar_quotes` (Kostenvoranschläge): seminar_id, version, price_list_version, Eingaben (JSON), Positionen (JSON), Summen; **unveränderlich** (neuer Voranschlag = neue Version).
- `price_lists` und `price_items`: versionierte Grunddaten (siehe 6).
- `event_registrations` (nur Großveranstaltungen): seminar_id, name, school (frei oder Partner-Verknüpfung), class, birth_date, email, ticket_code, confirmed_at, source (`self`|`upload`), uploaded_by, created_at; `event_attendances`: registration_id, session_id, present.
- Dokumente (Vereinbarung, Teilnahmeliste, Bericht) im privaten Speicher wie bei Mitgliedschaften.

Das Protokoll (Audit) bekommt Ereignisse wie `seminar.created`, `seminar.status_changed`, `seminar.quote_created`, nur mit Feldnamen. Namen und
Geburtsdaten der Anmeldungen stehen nie im allgemeinen Protokoll.

## 6 Kostenrechner (Kostenvoranschlag)

Nur Kostenvoranschläge. Grundlage ist die Excel-Tabelle. Die Grunddaten werden im Portal gepflegt und **versioniert**, damit alte Voranschläge ihren
Preisstand behalten (Preisstand Excel: Oktober 2025).

### 6.1 Grunddaten (Preisliste)

| Posten | Preis | Hinweis |
|---|---|---|
| Einfache Anfahrtspauschale | 10 € | innerhalb Tarifbereich ABC |
| Erweiterte Anfahrtspauschale | 45 € | außerhalb |
| Orgapauschale | 20 € pro Tag | |
| Vereinspauschale | 65 € einmalig | entfällt bei „ohne Verwaltungspauschale“ |
| Honorarsatz bis 2,5 h / bis 4,5 h / bis 6 h / ab 6 h | 100 / 135 / 170 / 200 € | je Teamende pro Tag |
| Zuschlag Seminarleitung kurz (< 4 h) / lang (≥ 4 h) | 30 / 50 € | |
| Materialpauschale | 32 € | bis 25 TN |
| Materialzuschlag Großgruppe | 4 € | je weitere 5 TN ab 25 |
| Pinnwand (Ausleihe) | 41 € | |
| Material An- und Abfahrt | 35 € (Berlin/ABC) / 105 € (sonst) | |

### 6.2 Formel je Leistungsart

Eingaben: Tage (bei Coaching/Wahl „Treffen“), Teamende, Stunden pro Tag, Gruppengröße, Anfahrt innerhalb ABC ja/nein, ohne Verwaltungspauschale ja/nein,
Anzahl Fahrten Material, Anzahl Pinnwände.

Positionen (Gesamt = Summe, mit Menge × Einzelpreis × Tage):

1. Honorar der Teamenden: Teamende × Satz nach Stunden pro Tag × Tage
2. Anfahrtspauschale: Teamende × (10 € oder 45 €) × Tage
3. Seminarleitung: Anzahl SL × Zuschlag (kurz/lang) × Tage
4. Orgapauschale: 20 € × Tage
5. Vereinspauschale: 65 € einmalig, außer „ohne Verwaltungspauschale“
6. Materialpauschale: Faktor × 32 € × Tage
7. Materialzuschlag: ((Gruppe − 25) / 5) × 4 € × Tage, nie unter 0
8. Material An-/Abfahrt: Fahrten × (35 € oder 105 €) × Tage
9. Pinnwand: Anzahl × 41 € × Tage

Die vier Blätter unterscheiden sich in den **Standardwerten**:

| Leistungsart | Tage/Treffen | Teamende | Stunden/Tag | SL | Materialfaktor |
|---|---|---|---|---|---|
| SV-Tage | 3 | 3 | – (Honorar fest 200 €) | 1 | 1 |
| SV-Fahrt | 2 | 6 | 8 | 1 | 1 |
| SV-Coaching (Kurze Tagestreffen) | 2 | 3 | 6 | 0 | 0,3 |
| SV-Wahl | 1 | 2 | 2 | 0 | 0,3 |

### 6.3 Auffälligkeiten in der Excel-Tabelle (bitte klären)

1. **SV-Tage:** Das Honorar nimmt immer den Satz ab 6 h (200 €), die Stunden-Eingabe wirkt nicht. Bei SV-Fahrt/Coaching/Wahl gilt die Staffel. Soll im Portal überall die Staffel gelten?
2. **Materialzuschlag in SV-Tage/SV-Fahrt** kann negativ werden (Gruppe unter 25 TN), in Coaching/Wahl nicht. Im Portal vorgesehen: nie unter 0.
3. **SV-Wahl:** Die Vereinspauschale ist fest 1 (der Schalter „ohne Verwaltungspauschale“ wirkt nicht). Soll der Schalter überall gelten?
4. **SV-Fahrt:** Die Summe läuft über F4:F14 statt F4:F12 (nur optisch). Das Portal summiert alle Positionen.
5. **Blatt „Tabelle1“ (Vertragspauschale):** Die Beschriftung sagt 10 %, die Formel in Spalte B rechnet ein Viertel (B4/4), die in Spalte C zehn Prozent vom Gesamtpreis. Welche Regel gilt? Gehört das zum Kostenvoranschlag?
6. **Start-Blatt:** Nennt zusätzlich „SV-Begleitung“, „Workshopbörse“ und „Wahlunterstützung“ ohne eigenes Blatt. Sind das weitere Leistungsarten oder alte Namen von SV-Coaching/SV-Wahl?

### 6.4 Ausgabe

Der Kostenvoranschlag erscheint als Seite und als druckbares PDF (Positionen, Summe, Preisstand, Gültigkeit, Hinweis „unverbindlich“). Er wird
am Seminar gespeichert, bleibt unveränderlich und kann als neue Version überarbeitet werden. Eine Verwaltungspauschale (siehe 6.3) kommt als eigene
Position, wenn sie so gewollt ist.

## 7 Teamende bewerben sich

- Die Koordination gibt ein terminiertes Seminar mit Bedarf frei (z. B. 2 Teamende).
- Teamende sehen offene Seminare (Ort grob, Termine, Honorar-Überblick aus dem Voranschlag) und bewerben sich oder ziehen zurück.
- Die Koordination bestätigt oder lehnt ab, bis der Bedarf gedeckt ist; Überschneidungen mit eigenen bestätigten Terminen werden angezeigt.
- Bestätigte Teamende erhalten eine Mail, Änderungen und Absagen lösen Benachrichtigungen aus.

## 8 Anmeldesystem für Großveranstaltungen

- **Selbstanmeldung (öffentlich, ohne Konto):** Name, Schule, Klasse, Geburtsdatum, E-Mail. Absenden erzeugt eine **Anmeldebestätigung per E-Mail**
  mit Ticketcode (Double-Opt-in-Link, damit die Adresse stimmt). Schutz wie bei „Mitglied werden“: Drosselung, Honeypot, neutrale Antworten bei Dubletten.
- **Upload im Auftrag von Lehrkräften:** Die Koordination lädt eine CSV/Excel-Liste hoch (alles oder nichts, Fehler je Zeile, wie beim Schulimport).
  Aus den Zeilen entstehen Anmeldungen; Bestätigung/Ticket gehen an die angegebene E-Mail oder gesammelt an die Lehrkraft (zu klären).
- **Anwesenheit:** Je Einheit führt die Koordination eine Anwesenheitsliste (Häkchen, Suche, Ticketcode-Eingabe).
- **Teilnahmebestätigung:** Nach der Veranstaltung können SuS sich im System eine Teilnahmebestätigung ausgeben lassen (Ticketcode + E-Mail
  oder Link in der Mail); sie gilt nur für Tage mit erfasster Anwesenheit (PDF).
- **Erlaubnis:** Die SuS sind für die Erlaubnis zur Teilnahme selbst verantwortlich. Die Anmeldeseite zeigt einen klaren Hinweis und verlangt
  ein Häkchen „Mir liegt die Erlaubnis meiner Schule bzw. Erziehungsberechtigten vor“; das Häkchen wird mit Version und Zeit gespeichert.
- **Datenschutz:** Datenschutzhinweis mit Einwilligungsprotokoll, Löschung der Teilnehmerdaten **drei Jahre nach der Veranstaltung** (täglicher
  Befehl wie `membership:prune`, Trockenlauf), Teilnehmerdaten nur für Koordination sichtbar, nicht im allgemeinen Protokoll. Die rechtliche Prüfung
  (Minderjährige, Erlaubnis, Geburtsdatum) sollte der Verein vor dem Start bestätigen.

## 9 Ausbaustufen

1. **Grundgerüst Schulseminare:** Modul, Katalog, Seminare mit Einheiten, Status-Pipeline, Verlauf, Verknüpfung mit Schulen, Rechte, Navigation.
2. **Kostenrechner:** Preisliste mit Versionen, Leistungsarten, Kostenvoranschlag (Seite, PDF), Tests gegen die Excel-Werte.
3. **Teamende:** Bewerbung, Zuweisung, Benachrichtigungen, Seite „Meine Seminare“.
4. **Großveranstaltungen:** Öffentliche Anmeldung, Bestätigung/Ticket, CSV-Upload.
5. **Anwesenheit und Teilnahmebestätigung:** Listen, PDF, Löschfristen.
6. **Nachbereitung:** Dokumente, Kurzbericht, Auswertungen.

## 10 Offene Fragen

1. Punkte aus 6.3 (Excel-Auffälligkeiten) klären.
2. Heißen die Leistungsarten im Portal wie in der Excel-Tabelle? Weitere Arten (Start-Blatt)?
3. Sollen Voranschläge Preise rundum netto oder brutto zeigen (Umsatzsteuer)?
4. Wer bestätigt den Kostenvoranschlag für die Schule, und wie (Mail/Dokument ablegen, später Schulkonto)?
5. Upload durch Lehrkräfte: Gehen Ticket und Bestätigung an die SuS oder gesammelt an die Lehrkraft?
6. Gibt es Platzgrenzen und Wartelisten bei Großveranstaltungen, und ein Kontingent je Schule?
7. Sehen Teamende das Honorar vor der Bewerbung?
8. Sollen Seminare im Kalender erscheinen (öffentlicher Terminkalender, iCal)?
9. Die rechtliche Prüfung der Anmeldung (Minderjährige, Einwilligung, 3 Jahre Aufbewahrung).
