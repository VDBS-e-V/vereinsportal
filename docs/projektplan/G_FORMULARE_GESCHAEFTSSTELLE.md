# Paket G: Formulare der Geschäftsstelle

Aufwand: **11 ET**. Ziel: Drei Vorgänge laufen digital statt per Papier und E-Mail: **Kostenrückerstattung**, **Fahrtkostenabrechnung**, **Materialbestellung/Beschaffung**. Alle drei teilen sich einen gemeinsamen Ablauf mit Prüfung, Freigabe, Verlauf, Belegen und Export für die Buchhaltung.

## 1 Ausgangslage
- Kein Formular- oder Antragsmodul. Vorhanden und wiederverwendbar: Vier-Augen-Freigabe aus den Rundschreiben (`mailings.approve`-Muster), private Dateiablage (Muster `MailAttachments`, `OnboardingDocuments`), Preisverwaltung für Seminare (Preislisten mit Gültigkeitsstart, Muster für Sätze wie den Kilometersatz), Aufgabenübersicht (`TaskOverview`), Benachrichtigungseinstellungen, Protokoll, CSV-Export mit Formelschutz, Eingabekomponenten.
- Kein Zahlungsverkehr im Portal. Das Portal bereitet nur vor und exportiert.

## 2 Entscheidungen

| Frage | Entscheidung / Annahme |
|---|---|
| Wer stellt Anträge? | **Teamende, Mitarbeitende, Koordination, Bildungsreferenten, Geschäftsstelle und Vorstand mit Konto** (Capability `forms.submit`); **Mitglieder nicht**. **Teamende ohne Konto können keine Anträge stellen** (Antwort R2b): Anträge gehen erst mit dem Teamkonto. |
| Wer prüft? | **Alles läuft über die Geschäftsstelle** (`forms.manage`): formale Prüfung, Rückfragen, Freigabe bis zur Grenze, Auszahlung vermerken (Antwort 19). |
| Wer gibt frei? | **Bis 50 € Gesamtsumme** die Geschäftsstelle. **Über 50 €** zusätzlich eine Person der Rolle **Geschäftsführung oder Vorstand** (`forms.approve`); es gibt **keinen Höchstbetrag**, hohe Beträge werden nur zur Bewilligung vorgelegt. Die Grenze ist in `form_settings` einstellbar. Niemand gibt ein eigenes Formular frei. Gilt für alle drei Formulartypen. Rollen: Paket I. |
| Geld | In Cent als Ganzzahl, nur Euro. Rundung auf volle Cent je Position. Server rechnet, nicht der Browser. |
| Belege | PDF, JPEG, PNG (hier sind Bilder erlaubt, es sind Belegfotos), je Datei 15 MB, je Antrag 100 MB. Pflicht für alle Positionen außer Kilometerpauschale. |
| Belegfrist | **Belegdatum im Kalenderjahr J, einreichbar bis Ende Februar von J+1** (Antwort 21). Beispiel: Belege aus 2026 können von Januar 2026 bis Ende Februar 2027 eingereicht werden. **Harte Sperre**; die Geschäftsstelle (`forms.manage`) kann pro Antrag eine **Ausnahme freischalten** (Begründung Pflicht, im Verlauf und Protokoll vermerkt). |
| IBAN | Prüfsumme (mod 97), Kontoinhaber:in. **Verschlüsselt gespeichert** und 30 Tage nach „ausgezahlt“ gelöscht. |
| Nummern | `E-2027-0001` (Erstattung), `F-2027-0001` (Fahrtkosten), `M-2027-0001` (Material); gleicher Dienst wie die Seminarnummer (Paket B). |
| Zahlung | Außerhalb des Portals. Das Portal liefert Sammelliste (CSV) und später eine SEPA-Datei (G8, optional). |
| Seminarbezug | Jeder Antrag kann einem Seminar (Nummer) zugeordnet werden; die Seminarseite zeigt Summen (nur Zahlen). |

## 3 Gemeinsames Datenmodell

**`form_requests`**
| Spalte | Hinweis |
|---|---|
| id, number (unique), type | `expense`, `travel`, `purchase` |
| applicant_user_id | FK null (Link-Anträge: null) |
| applicant_name, applicant_email | bei Link-Antrag Pflicht, bei Konto aus Person |
| seminar_id | FK null |
| cost_center | string(40) null |
| purpose | string(300) |
| status | `draft`, `submitted`, `in_review`, `returned`, `approved`, `rejected`, `paid`, `ordered`, `delivered`, `closed` |
| total_cents | uint |
| approval_level | `office|board|board2` (aus Betrag und Art) |
| submitted_at, checked_by_user_id, checked_at, approved_by_user_id, approved_at, second_approved_by_user_id, second_approved_at | |
| paid_at, paid_reference | string(100) |
| iban_encrypted, account_holder | verschlüsselt (`encrypted` cast) |
| data | json: typspezifische Felder |
| rejection_note | string(500) |
| retention_until | date |

**`form_request_items`**: request_id, position, date, kind (Fahrt, Unterkunft, Verpflegung, Material, Sonstiges, Ticket, Parken), description (200), quantity (decimal), unit_cents, amount_cents, extra json (km, Start, Ziel, Verkehrsmittel, Lieferant, Artikelnummer, Link).

**`form_request_files`**: request_id, item_id null, original_name, path, mime, size.

**`form_request_events`**: request_id, type, actor_user_id null, actor_label, comment (500), occurred_at.

**`form_settings`**: key, value (z. B. `km_rate_cents` = 30, Grenzen). Pflegeseite für die Geschäftsstelle (kein FTP nötig).

## 4 Arbeitspakete

### G1 Gemeinsamer Ablauf und Infrastruktur (3 ET)
- Migrationen und Modelle; `FormRequests` (Dienst): `create`, `saveDraft`, `submit`, `startReview`, `returnToApplicant(note)`, `approve(by)`, `reject(by, note)`, `markPaid(by, reference)`, `markOrdered`, `markDelivered`, `close`. Statusmaschine mit erlaubten Übergängen, Fehler als `DomainException` wie im Rundschreiben-Dienst.
- **Freigabelogik** `ApprovalPolicy`: aus Art, Betrag und Einstellungen ergibt sich `approval_level`; Prüfung „nicht selbst“; Vier-Augen bei `board2` (zwei verschiedene Personen mit `forms.approve`).
- **Belege** `FormFiles` (Muster `MailAttachments`): Typ-, Größen-, Gesamtprüfung, Zufallsname, privater Speicher, Download per Controller mit Berechtigung (Antragstellende, `forms.manage`, `forms.approve`).
- **Benachrichtigungen:** Mail an Antragstellende bei Rückfrage, Freigabe, Ablehnung, Auszahlung; an Geschäftsstelle bei neuem Antrag; an Vorstand bei Freigabebedarf (Katalog `NotificationPreferences`: `form_submitted`, `form_approval`). Kein Betrag und kein Beleginhalt in der Mail, nur Nummer und Art.
- **Verlauf** je Antrag (wer, was, wann, Kommentar), Protokoll nur Art + Nummer.
- **Aufgabenübersicht:** „X Anträge warten auf Prüfung/Freigabe“ (nur Zahlen).
- Zähler und Nummernformat über den Dienst aus Paket B (`SeminarNumbers` wird zu `Numbers`, Präfix pro Typ).
- Tests (`FormRequestFlowTest`): erlaubte/verbotene Statusübergänge, Selbstfreigabe verboten, Vier-Augen, Schwellen aus Einstellungen, Protokoll ohne Beträge, Benachrichtigungen.

### G2 Kostenrückerstattung (2,5 ET)
Seite **Meine Anträge** (Bereich nach Rolle, z. B. Teamende) und **Neuer Antrag › Kostenrückerstattung**.

Formularaufbau (Schritte mit Zwischenspeichern als Entwurf):
1. **Anlass:** Seminar (Auswahl mit Suche nach Nummer, optional), Zweck (Text), Kostenstelle (optional).
2. **Positionen** (beliebig viele, Wiederholfeld wie bei Schul-Ansprechpersonen): Datum, Art (Fahrt, Unterkunft, Verpflegung, Material, Sonstiges), Beschreibung, Betrag in Euro, **Beleg** (Upload je Position).
3. **Auszahlung:** Kontoinhaber:in, IBAN (Prüfung, Leerzeichen toleriert), Hinweis zur Löschung nach 30 Tagen.
4. **Erklärung:** „Die Angaben sind richtig, die Belege sind Originalausgaben, keine Doppelerstattung.“ (Pflichthäkchen).
5. **Zusammenfassung** mit Summe, Absenden.

Regeln: Beträge > 0, Datum nicht in der Zukunft; **Belegfrist** wie in Abschnitt 2 (Belegdatum im Kalenderjahr J, Einreichung bis 28./29.02. von J+1); Beleg je Position Pflicht, Gesamtsumme = Summe der Positionen (Server).

Bearbeitung nach „Rückfrage“: Antragstellende sehen den Kommentar, ändern, senden erneut.

Tests: Summenrechnung, Belegpflicht, IBAN-Prüfung, Entwurf speichern/fortsetzen, Rückfrage-Schleife, fremde Anträge unsichtbar.

### G3 Fahrtkostenabrechnung (1,5 ET)
Eigenes Formular für **Fahrten** mit Pauschale oder Ticket:
- Je Fahrt: Datum, Start, Ziel, Zweck/Seminar, Verkehrsmittel (**PKW**, Bahn, ÖPNV, Fahrrad/zu Fuß keine Kosten, Mitfahrt), **Hin- und Rückfahrt** (Häkchen verdoppelt Strecke), Kilometer (nur PKW), Fahrkosten per Ticket (Bahn/ÖPNV: Betrag + Beleg), Parkgebühr/Maut (+ Beleg).
- **Berechnung auf dem Server:** PKW: `Kilometer × km_rate_cents` (Standard 30 Cent, **Einstellung** in `form_settings`, Gültigkeit ab Datum für spätere Änderungen), Obergrenze je Fahrt einstellbar (Plausibilität, z. B. 1 000 km, Warnung).
- Mitfahrende: Anzahl (Information; kein Zuschlag in dieser Runde).
- Wegstrecke: manuelle Eingabe; keine Karten-API (Datenschutz, Kosten). Hinweis „Bitte die kürzeste übliche Strecke angeben.“
- Auszahlung/Erklärung wie G2.
- Tests: Kilometerberechnung inkl. Hin-/Rückfahrt, Satzwechsel nach Datum, Belegpflicht für Tickets, Plausibilitätswarnung.

### G4 Materialbestellung / Beschaffung (2 ET)
Antrag **vor** dem Kauf:
- Kopf: Zweck/Begründung, Seminar (optional), Kostenstelle, gewünschter Termin, Dringlichkeit (normal/dringend).
- Positionen: Bezeichnung, Menge, Einzelpreis brutto, Lieferant/Link/Artikelnummer, optional Angebot als Anhang.
- Lieferung: Lieferadresse (Auswahl: Geschäftsstelle / andere Adresse), Ansprechperson.
- Freigabe nach Summe (Grenzen in `form_settings`), Verlauf `submitted → in_review → approved → ordered → delivered → closed`.
- Nach Freigabe trägt die Geschäftsstelle „bestellt am“, „geliefert am“ und die Rechnung/den Beleg ein; Abschluss, wenn die Rechnung zugeordnet ist.
- Optional Weiterführung: Aus einer gelieferten Bestellung kann direkt eine Kostenrückerstattung entstehen, wenn jemand ausgelegt hat (Knopf, kopiert Positionen).
- Tests: Schwellen und Freigabestufen, Statusfolge bis Abschluss, Rechnung nach Freigabe.

### G5 Bearbeitung durch die Geschäftsstelle und Export (1,5 ET)
Bereich **Geschäftsstelle › Anträge**:
- Eingangsliste mit Filter (Art, Status, Zeitraum, Seminar, Antragsteller), Suche nach Nummer, Zähler im Seitenkopf.
- Prüfseite: Positionen, Belege (Vorschau, Download), Verlauf, Aktionen: Rückfrage, Prüfung abschließen, Freigabe vorlegen (bei Stufe Vorstand), Ablehnen mit Begründung, „Ausgezahlt“ mit Datum und Verweis.
- **Export 1: Auszahlungsliste** (Auswahl freigegebener Anträge). Trennzeichen `;`, Dezimalkomma, UTF-8 mit BOM, Formelschutz. Spalten: `Antragsnummer; Art; Eingereicht am; Freigegeben am; Antragsteller:in (Name); E-Mail; Kontoinhaber:in; IBAN; Betrag gesamt (EUR); Verwendungszweck (z. B. "E-2027-0001 Erstattung Seminar S-2027-0042"); Kostenstelle; Seminar-Nr.; Geprüft von; Freigegeben von; Ausnahme Belegfrist (ja/nein); Bemerkung`. Nach Export Markierung „Sammelliste erstellt“; „Ausgezahlt“ setzt Datum.
- **Export 2: Buchungsliste** (Zeitraum, Typ, Status wählbar), eine Zeile je Position: `Antragsnummer; Position; Belegdatum; Art; Beschreibung; Menge; Einzelbetrag (EUR); Betrag (EUR); Kilometer; Start; Ziel; Verkehrsmittel; Lieferant; Kostenstelle; Seminar-Nr.; Seminar-Titel; Antragsteller:in; Freigabe-Datum; Auszahlungsdatum; Auszahlungsreferenz; Belegdateiname; Belegnummer`.
- **Export 3: Jahresübersicht** je Kostenstelle und Art (Summen), für die Abstimmung mit der Kasse.
- Einstellungen: km-Satz, Grenzen, Fristen (`forms.manage`).
- Tests: Filter, Export-Spalten, IBAN nur im Export und nur für Berechtigte, Formelschutz, Einstellungen wirken.

### G6 Kostenstellen und Übergangsregel (0,5 ET)
- Tabelle `cost_centers` (`code` unique, `name`, `active`), Pflege durch `forms.manage` und `forms.approve` (langfristig eine Stelle in der Geschäftsstelle; **aktuell der Kassierer im Vorstand**, Antwort R13). Auswahlfeld statt Freitext in allen Formularen und im Seminar (Paket B3).
- **Übergangsregel:** Solange die Stelle in der Geschäftsstelle nicht besetzt ist, übernimmt die Zusatzrolle „Kassenführung“ (Vorstandsmitglied Kasse) die Rechte von `forms.manage`. Die Zusatzrolle wird später entzogen (Rollenvergabe vorhanden). Die Kassenführung darf die **Prüfung** ihrer eigenen Anträge selbst vornehmen; im Verlauf steht „Selbstprüfung“ für alle sichtbar. Andere Vorstandsmitglieder können sie ebenfalls prüfen. Die **Freigabe über 50 €** gibt immer eine andere Person mit `forms.approve`. **Freigabe** eigener Anträge gibt **immer eine andere Person** (Antwort Q8), auch bis 50 €.
- Tests: Kostenstellen-Pflege, Auswahl in Formularen, Selbstprüfung der Kassenführung erlaubt und sichtbar vermerkt, Selbstfreigabe über 50 € gesperrt, Zusatzrolle gibt und nimmt Recht.

### G7 Aufbewahrung und Datenschutz (0,5 ET)
- Befehl `forms:prune`: IBAN und Kontoinhaber 30 Tage nach „ausgezahlt/abgelehnt/geschlossen“ löschen; Belege und Anträge nach 10 Jahren (Ende des Jahres der Auszahlung) löschen; Entwürfe ohne Änderung nach 90 Tagen löschen. Systemsteuerung (hoch), Planer täglich.
- Konto löschen (`account:purge`): Anträge bleiben (steuerliche Pflicht), Name wird anonymisiert, IBAN gelöscht. In `AccountDeletion` ergänzen.
- Datenschutzerklärung: Abschnitt „Kostenerstattung und Beschaffung“.

### G8 SEPA-Datei: entfällt (Antwort 23)
Nur die CSV-Sammelliste (G5). Eine SEPA-Datei kann später nachgerüstet werden.

### G9 Später (nicht in den 12 ET)
Honorarabrechnung Teamende (Stunden × Satz aus Preisliste), Urlaubs-/Abwesenheitsanträge. Beide laufen auf demselben Ablauf (G1) und brauchen nur ein neues Formular und eine Berechnungsregel (je ca. 2–3 ET).

### G10 Tests (im Aufwand enthalten) — Gesamtübersicht
`FormRequestFlowTest`, `ExpenseFormTest`, `TravelFormTest`, `PurchaseFormTest`, `FormExportTest`, `FormLinkTest`, `FormPruneTest`; zusätzlich Navigation- und Berechtigungstests (`forms.*`).

## 5 Rechte und Bereiche
| Seite | Bereich | Recht |
|---|---|---|
| Neuer Antrag, Meine Anträge | Bereich der Person (Teamende, Koordination, Bildungsreferenten, Geschäftsstelle, Vorstand) | `forms.submit` |
| Anträge, Prüfung, Export, Einstellungen | Geschäftsstelle | `forms.manage` |
| Freigaben ab 50 € | Geschäftsführung, Vorstand | `forms.approve` |
| Anträge über den persönlichen Zugang | öffentlich, Code (Paket C0) | – |

## 6 Abnahme (M5, Teil 1)
1. Erstattung mit zwei Positionen und Belegen einreichen, Rückfrage, Korrektur, Freigabe, Auszahlungsvermerk.
2. Fahrtkosten PKW 120 km Hin und zurück ergibt 72,00 € bei 30 Cent (über 50 €: zweite Freigabe).
3. Materialantrag über 320 € läuft über Geschäftsstelle und danach Geschäftsführung oder Vorstand.
3a. Antrag unter 50 € wird allein durch die Geschäftsstelle freigegeben.
4. Sammelliste herunterladen, IBAN erscheint nur dort.
5. 31 Tage nach Auszahlung ist die IBAN gelöscht (Testdatum).

## 7 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| IBAN-Missbrauch | Verschlüsselung, Sichtbarkeit nur im Export für `forms.manage`, Löschfrist, Protokoll ohne Inhalt |
| Falsche Beträge | Server rechnet, Plausibilitätswarnungen, Prüfung durch Geschäftsstelle |
| Steuerrecht (Pauschalen, Aufbewahrung) | Fristen und Sätze einstellbar, Bestätigung durch Steuerberatung |
| Belege als Fotos groß | Größengrenzen, Hinweis „Bitte Seite flach fotografieren“ |
| Selbstfreigabe | im Dienst blockiert, getestet |

## 8 Offene Fragen
Siehe `01_OFFENE_FRAGEN.md`: R3 (Rollen Mitarbeitende und Geschäftsführung), R5 (Belegfrist hart oder Warnung), R6 (50 €: Gesamtsumme oder je Position; Material), R10 (CSV-Spalten).
