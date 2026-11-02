# Entwicklungsplan: Rundschreiben und Mitteilungen

Stand: 2. November 2026. Grundlage: das Konzept `docs/64_RUNDSCHREIBEN_KONZEPT.md` (Merkmale **A1 bis I3**). Dieser Plan sagt, **was in welcher Reihenfolge gebaut wird**, mit Datenmodell, Klassen, Tests und Abnahmekriterien je Phase.

## 1. Arbeitsweise

- **Eine Phase = eine Lieferung** auf dem Branch, mit Migration (falls nötig), Tests, Doku-Update und einer Liste der Befehle (`migrate`, `view:clear`, `npm run build`, Pest), wie bisher.
- **Reihenfolge der Arbeit je Phase:** (1) Migration und Modelle, (2) Dienste und Aufträge, (3) Controller und Routen, (4) Ansichten, (5) Tests, (6) Doku `docs/63_RUNDSCHREIBEN.md`.
- **Fertig heißt (Definition of Done):**
  1. Alle Merkmale der Phase haben mindestens einen Test, der sie prüft.
  2. Die ganze Pest-Suite läuft grün.
  3. Neue Ansichten nutzen nur Komponenten des Designsystems, keine Sonderdesigns ohne Zustimmung. Tastatur und Screenreader funktionieren (WCAG 2.2 AA).
  4. Das Protokoll enthält nie Adressen oder Textinhalte (H2, H4).
  5. Doku und Plan sind aktualisiert.
- **Nicht verhandelbar:** Einzelversand an jede Person, Platzhalter-Prüfung, kein Tracking-Pixel.

## 2. Annahmen für die offenen Entscheidungen

Damit die Arbeit beginnen kann, gelten diese Voreinstellungen. Jede ist über Konfiguration änderbar und im Plan markiert (**Annahme**). Sie ersetzen nicht Ihre Entscheidung.

| # | Frage | Annahme |
|---|---|---|
| 1 | Versanddienst und Betrieb | Beliebiger SMTP-Dienst über `MAIL_MAILER`. Warteschlange `database` mit einem Worker (`php artisan queue:work`, produktiv als Dienst). Bounce-Webhooks erst in Phase 7, nach Wahl des Dienstes. |
| 2 | Satzung, Einladungsfrist | Einstellung `portal.mailings.invitation_notice_days`, Standard **14 Tage**, Hinweis „bitte mit der Satzung abgleichen“. |
| 3 | Freigabe | Einstellbar `portal.mailings.approval`: Schreiben an Mitglieder vom Vorstand ohne Freigabe, Schreiben ab **50 Empfängern** von der Geschäftsstelle mit Freigabe durch den Vorstand. |
| 4 | Tracking | **Keines.** Berichte nur zu Zustellung, Fehlern, Abmeldungen. |
| 5 | Anhänge | PDF, JPEG, PNG, zusammen höchstens **5 MB**. |
| 6 | Absender | Gemeinsame `MAIL_FROM_ADDRESS`, **Reply-To und Absendername je Bereich** (Vorstand, Geschäftsstelle, Koordination). |

## 3. Überblick

| Phase | Inhalt | Merkmale | Aufwand | Abhängigkeit |
|---|---|---|---|---|
| 1 | Hintergrundversand, Zustellstatus, Fortschritt | D1–D3, A1, A2, G1, H3, I1 | L | Worker lokal und produktiv |
| 2 | Abmelden, One-Click, Sperrliste | A6, C8, E2, E3, F1–F4 | L | Phase 1 |
| 3 | Entwürfe, Zeitplan, Freigabe, Absender | D4–D7, H1 | M | Phase 1 |
| 4 | Nachweis für Versammlungen | A3–A5 | M | Phase 1, Satzung |
| 5 | Empfänger ausbauen | B2–B7 | M bis L | Phase 1 |
| 6 | Inhalt ausbauen | C2–C7 | M | Phase 2 |
| 7 | Zustellqualität und Bounces | E1, E4, E5, G3 | M | Phase 1, 2; Versanddienst |
| 8 | Feinschliff | C9, C10, D8, I3 | S bis M | alle |

Empfohlener Start: **Phase 1 und 2 zusammen**, weil Zustellstatus und Abmeldung die Grundlage aller weiteren Merkmale sind. Reihenfolge danach: 4, 3, 5, 6, 7, 8.

---

## Phase 1: Zuverlässiger Hintergrundversand mit Zustellstatus

**Ziel:** Ein Schreiben wird nicht mehr im Seitenaufruf gesendet. Es gibt einen Status je Schreiben und je Adresse, einen Fortschritt, Abbruch und Wiederholung.

### Datenmodell (Migration `…_create_mailing_deliveries.php`)
- `mailings`: neue Spalten `status` (`queued`, `sending`, `done`, `cancelled`, `failed`), `queued_at`, `started_at`, `finished_at`, `cancelled_at`, `batch_id` (Laravel-Batch). Bestehende Zeilen gelten als `done`.
- `mailing_deliveries`: `id`, `mailing_id` (FK, löschen mit dem Schreiben), `email`, `first_name`, `last_name`, `fallback`, `group`, `status` (`pending`, `sending`, `sent`, `failed`, `skipped`), `attempts`, `error` (kurz, ohne Geheimnisse), `sent_at`, Zeitstempel. Index auf (`mailing_id`, `status`).
- Die Tabelle `job_batches` und `failed_jobs` gibt es bereits (Standard-Migration).

### Klassen
- `Mailings::queue(...)` ersetzt `sendGroups`/`sendTeam`: legt das Schreiben an, schreibt für jede Adresse eine Zeile in `mailing_deliveries` (Momentaufnahme der Empfänger zum Sendezeitpunkt), startet den Auftrag. Die Controller leiten danach auf die Fortschrittsseite.
- `Jobs\SendMailing`: teilt die Zustellzeilen in Pakete (Standard 25) und startet `Bus::batch([...SendMailingBatch])` mit `allowFailures()`; `then`/`finally` setzen den Endstatus.
- `Jobs\SendMailingBatch`: sendet je Zeile genau eine Mail. **Idempotenz:** Eine Zeile wird nur gesendet, wenn sie atomar von `pending` auf `sending` gesetzt werden konnte (`UPDATE … WHERE status='pending'`). Wiederholte Aufträge senden nie doppelt. Fehler werden in `error` und `attempts` festgehalten.
- Drosselung über Job-Middleware `RateLimited('mailings')`, Limit aus `portal.mailings.per_minute` (**Annahme:** 60). Wiederholung mit Wartezeit (Backoff 1, 5, 15 Minuten, höchstens 3 Versuche).
- `Console\PruneMailingDeliveries` (täglich im Planer): löscht Zustellzeilen älter als `portal.mailings.delivery_retention_days` (Standard **90**). Betreff, Text und Zahlen bleiben (H3).

### Oberfläche
- Neue Seite je Schreiben `…/{mailing}` (Routen `mailings.board.show`, `mailings.office.show`, `mailings.team.show`): Betreff, Gruppen, Status, Fortschrittsbalken (gesendet, offen, fehlgeschlagen), Liste der fehlgeschlagenen Adressen mit Grund (nur für Berechtigte), Knopf „Abbrechen“ und „Fehlgeschlagene erneut versuchen“.
- Aktualisierung ohne JavaScript über `<meta refresh>` solange der Status `queued` oder `sending` ist, mit JavaScript sanfter über einen JSON-Endpunkt.
- Die Verlaufsliste zeigt den Status und verlinkt auf die Seite.
- Systemsteuerung › Zustand: Prüfung „Warteschlange arbeitet“ (ältester wartender Auftrag, Hinweis auf fehlenden Worker). Anleitung für `queue:work` in der Doku.

### Tests
- Senden legt Zustellzeilen an und läuft mit `QUEUE_CONNECTION=sync` im Test bis `done`; Status, Zähler und `sent_at` stimmen.
- Ein fehlschlagender Versand einer Adresse setzt nur diese Zeile auf `failed` und verhindert den Rest nicht.
- Wiederholter Auftrag sendet **nicht doppelt** (Zeile `sent` bleibt unberührt).
- Abbrechen stoppt offene Zeilen (`skipped`), „erneut versuchen“ nimmt nur `failed`.
- Aufräumen löscht alte Zustellzeilen, nicht das Schreiben. Planer-Eintrag vorhanden.
- Berechtigung: Fortschritt sieht nur, wer das Schreiben sehen darf. Das Protokoll enthält keine Adressen.

### Abnahme
Ein Schreiben an 200 Testadressen kehrt in unter einer Sekunde zurück, wird vom Worker abgearbeitet, zeigt den Fortschritt und übersteht einen Abbruch des Workers mittendrin ohne Doppelversand.

### Risiken
- Worker läuft nicht (lokal oder produktiv): Mitigation durch die Zustandsprüfung und die Doku. Fallback `sync` nur für kleine Gruppen, nicht für Produktion.
- Datenbank-Warteschlange und viele Aufträge: Paketgröße und Limit konfigurierbar.

---

## Phase 2: Abmelden und Unterdrückungsliste

**Ziel:** Jede abbestellbare Mail enthält einen funktionierenden Abmeldelink und die Kopfzeilen für One-Click. Adressen mit harten Fehlern oder Beschwerden werden nicht mehr angeschrieben.

### Datenmodell
- `mail_unsubscribes`: `id`, `email` (kleingeschrieben), `topic` (`member_info`, `team_mailings`, `school_mailings`), `created_at`. Eindeutig je (`email`, `topic`).
- `mail_suppressions`: `id`, `email`, `reason` (`hard_bounce`, `complaint`, `manual`), `source`, `note`, `created_at`, `released_at`, `released_by_user_id`.
- `mailings.mandatory` (bool): Pflichtmitteilung. Nur wer `mailings.members` hat, darf sie setzen.

### Regeln
- **Themen je Gruppe:** Mitglieder → `member_info`, Teamende → `team_mailings`, Schulen → `school_mailings`. Interne Gruppen (Koordination, Bildungsreferenten, Vorstand, Geschäftsstelle) sind Organisationspost und nicht abbestellbar.
- **Pflichtmitteilung** (Einladung zur Versammlung): ignoriert Abmeldungen vom Thema, enthält keinen Abmeldelink, kennzeichnet sich im Fuß („Diese Nachricht ist eine Pflichtmitteilung des Vereins“). Gesperrte Adressen bekommen sie trotzdem nicht und erscheinen in der Liste der **Nicht Erreichten** (Phase 4).
- Abmeldung vom Thema wirkt auch für Konten (schreibt in `notification_preferences`), und umgekehrt zählt die Einstellung im Konto.

### Klassen und Routen
- `Support\MailUnsubscribeToken`: signierter Link (Laravel `URL::signedRoute`) mit E-Mail und Thema.
- Öffentliche Routen: `GET /abmelden/{token}` (Bestätigungsseite mit „Abmelden“ und „Wieder anmelden“), `POST /abmelden/{token}` (**CSRF-ausgenommen**, nimmt auch `List-Unsubscribe=One-Click` entgegen, antwortet 200).
- `MailingMail`: setzt `List-Unsubscribe: <https://…/abmelden/…>, <mailto:…>` und `List-Unsubscribe-Post: List-Unsubscribe=One-Click`, im Fuß der Link „Mitteilungen dieser Art abbestellen“. Ohne Abmeldelink bei Pflichtmitteilung und internen Gruppen.
- `MailRecipients`: filtert Abgemeldete und Gesperrte (zählt sie getrennt, B6).
- Ansicht „Gesperrte Adressen“ (Geschäftsstelle und Vorstand, `mailings.send`): Liste, manuelles Sperren, Freigeben mit Grund. Einträge werden protokolliert (ohne Adresse im allgemeinen Protokoll).

### Tests
- Link enthält gültige Signatur, manipulierte oder fremde Links liefern 403.
- Abmelden per Klick und per One-Click-POST (ohne CSRF-Token) trägt in `mail_unsubscribes` ein, Wiederanmelden löscht.
- Abgemeldete bekommen kein Schreiben dieses Themas, aber andere Themen.
- Pflichtmitteilung geht trotz Abmeldung, aber nicht an gesperrte Adressen.
- Kopfzeilen vorhanden bei abbestellbaren, fehlen bei Pflicht und intern.
- Konto-Einstellung und Abmeldung per Link wirken gegenseitig.

### Abnahme
Gmail zeigt bei einer Testmail den „Abbestellen“-Knopf, ein Klick dort trägt die Abmeldung ein, und das nächste Schreiben erreicht die Person nicht mehr.

### Risiken
- DKIM-Signatur der Kopfzeilen ist Sache des Mailservers. Hinweis in Doku und Phase 7.
- Rechtlicher Charakter der Pflichtmitteilung: Beschluss durch den Vorstand, Hinweis in der Oberfläche.

---

## Phase 3: Entwürfe, Zeitplan, Freigabe, Absender

**Ziel:** Schreiben lassen sich vorbereiten, einplanen und vor dem Versand prüfen lassen.

**Stand: umgesetzt (Update 3)**, siehe `docs/63_RUNDSCHREIBEN.md`.

### Datenmodell
- `mailings`: `status` erweitert um `draft`, `pending_approval`, `scheduled`. Spalten `send_at`, `approved_by_user_id`, `approved_at`, `created_by_user_id`, Eingabefelder des Formulars (JSON `payload`) für Entwürfe.
- Berechtigung `mailings.approve` (Vorstand). Einstellung `portal.mailings.approval` (Schwelle, Gruppen).

### Klassen
- `Mailings::saveDraft`, `submitForApproval`, `approve`, `reject` (mit Begründung), `schedule`.
- `Console\DispatchDueMailings` (jede Minute im Planer): startet freigegebene, fällige Schreiben. Die Zustandsseite zeigt den Planer-Herzschlag bereits.
- Absender je Bereich: `portal.mailings.senders` (`board`, `office`, `team` mit Name und Reply-To), im Formular angezeigt (D7).

### Oberfläche
- Formular: „Als Entwurf speichern“, „Zur Freigabe vorlegen“, „Planen für …“.
- Liste „Entwürfe und Freigaben“. Freigebende sehen Vorschau, Empfängerzahl und können freigeben oder mit Begründung zurückgeben. Keine Selbstfreigabe.

### Tests
- Entwurf lässt sich speichern, laden, ändern, löschen.
- Freigabe ist ab Schwelle Pflicht, Selbstfreigabe verweigert, Rückgabe mit Begründung erreicht die Autorin.
- Geplantes Schreiben geht zur Zeit raus, nicht davor, und nicht doppelt.
- Reply-To und Absendername je Bereich stehen in der Mail.
- Berechtigung `mailings.approve` nur beim Vorstand.

### Abnahme
Die Geschäftsstelle plant ein Rundschreiben für morgen früh, der Vorstand gibt es frei, es geht pünktlich raus.

---

## Phase 4: Nachweis für Versammlungen

**Ziel:** Die Einladung zur Mitgliederversammlung ist nachweisbar und fristgerecht.

**Stand: umgesetzt (Update 4)**, siehe `docs/63_RUNDSCHREIBEN.md`. Die Frist ist als Annahme (14 Tage) eingestellt, bis die Satzung geklärt ist.

### Umfang
- Einstellung `invitation_notice_days` (Annahme 14) und Eingabe „Termin der Versammlung“ (bestehende Terminangabe). Beim Planen und Senden Fristwarnung: „Zwischen Versand und Versammlung liegen nur 9 Tage. Ihre Satzung verlangt 14.“ Nur Warnung, kein Sperren.
- **Versandbescheinigung** je Schreiben (Seite mit Druckansicht): Versandzeitpunkt, Betreff, Text, Zahl der Empfänger, davon zugestellt, fehlgeschlagen, gesperrt. Kein Rechtsgutachten, aber ein belastbarer Beleg.
- **Nicht Erreichte**: Liste der Mitglieder ohne Adresse, mit gesperrter Adresse oder fehlgeschlagener Zustellung, mit Hinweis „Bitte postalisch einladen“ (Name, kein Zusatzdatum). Nur für `mailings.members`.

### Tests
- Fristwarnung erscheint unter der Frist und verschwindet darüber.
- Bescheinigung zeigt die Zahlen und ist nur für Berechtigte erreichbar, Druckansicht ohne Navigation.
- Liste der Nicht Erreichten enthält genau die richtigen Mitglieder.

### Abhängigkeit
Entscheidung 2 (Satzungswortlaut) bestimmt Standardfrist und Wortlaut der Hinweise.

---

## Phase 5: Empfänger ausbauen

**Ziel:** Treffsichere Empfängerwahl.

### Umfang
- **B2 Ausschlüsse:** Zweite Auswahl „außer …“ (Gruppen). Rechenweg: Vereinigung der Gruppen minus Ausschlüsse, danach Duplikate und Sperren.
- **B3 Einzelpersonen:** Suchfeld mit Auswahl (Konten und Mitgliedschaften), Chips mit Entfernen.
- **B4 Kontextstart:** Schaltfläche „Teamende anschreiben“ im Seminar, „Ansprechpersonen anschreiben“ bei der Schule, „Teilnehmende anschreiben“ bei der Veranstaltung. Das Formular öffnet mit vorbelegten Empfängern (Parameter in der Adresse, serverseitig geprüft).
- **B5/B7 Filter und Segmente (später in der Phase):** Filter wie „Einarbeitung offen“, „Bundesland“; Segmente mit Namen speichern (`mail_segments`, Definition als JSON, bei jedem Versand neu berechnet).
- **B6 Zahlen:** Vorschau mit „n erreichbar, m abgemeldet, k gesperrt“.

### Tests
- Ausschlüsse, Einzelpersonen und Vereinigungen liefern je Adresse genau einen Empfänger.
- Kontextstart prüft Berechtigung am Seminar, an der Schule, an der Veranstaltung.
- Segmentberechnung ist beim Versand aktuell, nicht beim Speichern.

---

## Phase 6: Inhalt ausbauen

### Umfang
- **C2 Standardwerte:** Syntax `{vorname|Mitglied}`. Der Parser kennt den Wert hinter `|`, die Platzhalter-Prüfung bleibt streng (nur bekannte Namen).
- **C3 Textbausteine:** Tabelle `mail_snippets` (Name, Inhalt), Verwaltung unter Mailvorlagen, Einfügen im Editor per Auswahl.
- **C4 Einfügen per Klick:** kleines Skript (`resources/js/mailings.js`), das einen Platzhalter an der Cursorposition im Editor (TinyMCE) einsetzt. Ohne JavaScript bleibt die Liste zum Abtippen.
- **C5 Anhänge:** Tabelle `mailing_attachments`, privater Speicher, Typ- und Größenprüfung (Annahme: PDF, JPEG, PNG, zusammen 5 MB), Löschen mit dem Schreiben nach der Frist.
- **C6 Textversion:** `MailingMail` liefert zusätzlich `text` (aus dem HTML erzeugt: Absätze, Listen, Links als „Text (Adresse)“).
- **C7 Barrierefreiheitsprüfung** `Support\MailAccessibilityCheck`: Linktexte wie „hier“, leere Überschriften, übersprungene Überschriftenebenen, Bilder ohne Alternativtext, sehr lange Absätze. Zeigt Hinweise in der Vorschau, sperrt nicht. Die Standardvorlage der Mail hat gesetzte Sprache und geprüften Kontrast.

### Tests
- Standardwert greift nur bei leerem Wert, unbekannte Namen werden weiter abgelehnt.
- Textbaustein wird eingefügt und bei Änderung des Bausteins nicht rückwirkend verändert.
- Anhang: Typ, Größe, Zugriff nur für Berechtigte, Mail enthält den Anhang.
- Textversion enthält Linkadressen und keine Tags.
- Prüfung findet „hier“-Links und übersprungene Überschriften.

---

## Phase 7: Zustellqualität und Bounces

### Umfang
- **E1 Absenderprüfung** in der Systemsteuerung: DNS-Abfrage für die Absenderdomain (SPF vorhanden, DMARC-Eintrag vorhanden, optional DKIM-Selektor aus der Konfiguration). Ergebnis als Ampel mit Erklärung, kein Sendestopp.
- **E4 Bounces und Beschwerden:** Eingehender Webhook (`POST /webhooks/mail/{dienst}` mit gemeinsamem Geheimnis und Signaturprüfung), Adapter-Schnittstelle `BounceAdapter` mit einer Umsetzung für den gewählten Dienst. Harte Bounces und Beschwerden schreiben in `mail_suppressions`, weiche zählen mit (Sperre nach 5 in Folge, Annahme).
- **E5 Warnung:** Überschreitet die Fehlerquote eines Versands 5 % (einstellbar), erscheint ein Hinweis auf der Fortschrittsseite und in der Systemsteuerung.
- **G3 Verlauf:** Seite „Zustellqualität“ mit Fehlerquote, Abmeldungen und Sperren im Zeitverlauf (Tabelle, keine Diagramme nötig).

### Abhängigkeit
Entscheidung 1 (Versanddienst) legt den Adapter fest. Ohne Dienstwahl bleibt E1 und die Warnung, E4 wartet.

### Tests
- DNS-Prüfung mit simulierten Antworten (Antwort fehlt, SPF ohne `-all`, DMARC vorhanden).
- Webhook lehnt ungültige Signatur ab, harte Bounces sperren sofort, Beschwerden sperren sofort, weiche erst nach der Schwelle.
- Warnung bei hoher Fehlerquote.

---

## Phase 8: Feinschliff

- **C9 Mehrsprachige Vorlagen** (nur falls gewünscht: Sprache je Vorlage, Auswahl beim Schreiben).
- **C10 Versionsverlauf** der Vorlagen (Tabelle `mail_template_versions`, Wiederherstellen).
- **D8 Wiederholung an nicht Erreichte:** neues Schreiben mit den fehlgeschlagenen Adressen eines früheren.
- **I3 Doku in einfacher Sprache** für Vorstand und Geschäftsstelle (eine Seite je Aufgabe: „Einladung zur Versammlung verschicken“, „Nachricht an Teamende“, „Was bedeutet Abmelden?“).

---

## 4. Querschnitt

### Konfiguration (neu `config/portal.php › mailings`)
`per_minute` (60), `batch_size` (25), `delivery_retention_days` (90), `invitation_notice_days` (14), `approval` (Schwelle 50, Gruppen), `senders` (je Bereich Name und Reply-To), `attachments` (Typen, Größe), `failure_warning_percent` (5), `soft_bounce_limit` (5).

### Berechtigungen
Bestehend: `mailings.send`, `mailings.members`, `mailings.team`, `mail_templates.manage`. Neu: `mailings.approve` (Vorstand, Phase 3).

### Protokoll
Neue Ereignisse (nur Art und Zahlen): Schreiben freigegeben, zurückgegeben, abgebrochen, Adresse gesperrt oder freigegeben (ohne Adresse), Abmeldung per Link (ohne Adresse).

### Teststrategie
- Pest-Feature-Tests je Phase, Datei `tests/Feature/Mailings/…` nach Thema (`DeliveryTest`, `UnsubscribeTest`, `ApprovalTest`, …).
- Versand im Test mit `Mail::fake()` und `QUEUE_CONNECTION=sync`. Zeit mit `Carbon::setTestNow`.
- Kein Test schreibt in `storage/logs` oder lässt Dateien zurück.

### Rollout
1. Vor Phase 1: Worker einrichten (lokal `php artisan queue:work`, produktiv als Dienst oder Supervisor) und Zustandsprüfung freischalten.
2. Je Phase `php artisan migrate`, `view:clear`, `npm run build`, Pest.
3. Nach Phase 2: Testversand an eigene Adresse mit Gmail und einem zweiten Anbieter, Abmeldung prüfen.
4. Nach Phase 4: Probelauf der Einladung mit der Bescheinigung vor der echten Versammlung.

### Risiken im Überblick
| Risiko | Folge | Gegenmaßnahme |
|---|---|---|
| Worker fällt aus | Schreiben bleiben liegen | Zustandsprüfung, Hinweis auf der Fortschrittsseite, Doku |
| Doppelversand bei Wiederholung | Beschwerden, Vertrauensverlust | Atomares Umsetzen der Zustellzeile, Test |
| Abmeldung greift zu spät | Beschwerden, Rechtsrisiko | Wirkung beim nächsten Versand, Prüfung beim Senden, nicht beim Anlegen |
| Satzung verlangt Brief | E-Mail-Einladung unwirksam | Hinweis, Liste der Nicht Erreichten, Entscheidung 2 |
| Zustellzeilen enthalten Adressen | Datenschutz | Aufbewahrung 90 Tage, Aufräumen, Zugriff nur für Berechtigte, nie im Protokoll |
| Dienstwahl verzögert Bounces | Schlechte Adressen bleiben | Phase 7 zuletzt, manuelle Sperrliste ab Phase 2 |

## 5. Nächster Schritt

Zur Freigabe: **Phase 1 und 2** zusammen. Vorher brauche ich von Ihnen nur ein Ja oder Nein zu den Annahmen in Abschnitt 2 (besonders Freigabeschwelle und Anhänge). Für Phase 4 die Satzungsregel zur Einladung, für Phase 7 den Versanddienst.
