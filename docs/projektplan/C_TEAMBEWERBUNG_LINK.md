# Paket C: Import der Mailingliste, persönlicher Zugang, Teambewerbung ohne Konto, gemeinsame Teaminformation

Aufwand: **12 ET**. Ziel: Die bestehende Mailingliste wird importiert. **Jede Person bekommt einen persönlichen Zugang mit Code**, der sie eindeutig einer Person zuordnet. Damit bewerben sich Teamende auf Seminare, bis die Teamkonten kommen (zeitnah danach). Nach der Auswahl wird das **ganze Team gemeinsam** informiert.

## 1 Ausgangslage
- `SeminarApplication` verlangt `user_id` (Konto der Rolle Team, Capability `seminars.apply`). Bewerben, Zurückziehen, Entscheidungen, Zusammensetzung (`review`), Vorschlag (`suggest`), Überschneidungswarnung (`conflicts`) und Benachrichtigungen (`send`) arbeiten mit `User`.
- Die Koordination sieht je Seminar die Bewerbungen mit Profil (Alter, Geschlecht, Kompetenzen, Erfahrung) und kann nach dem Stichtag entscheiden.
- Formularschutz (`bot guard`), E-Mail-Bestätigung per signiertem Link (Muster: Veranstaltungsanmeldung, Beitrittsantrag) und die Einzel-Adressen im Rundschreiben (`e:`-Marken) sind vorhanden.

## 1a Neue Anforderung (Antwort 10)
Die aktuelle Mailingliste wird ins Portal importiert. Alle auf der Liste bekommen einen **individuellen Zugang mit Code**; der Code ordnet die Handlung (Bewerbung, Antrag, später Kontoaktivierung) **eindeutig der Person zu**. Bewerben ohne Teamkonto bleibt auch nach Einführung der Teamkonten möglich. Folgen:
- Die Bewerbung braucht **keine E-Mail-Bestätigung** mehr: Der Zugang ging an die bekannte Adresse. Der allgemeine Seminarlink bleibt als **optionaler Zweitweg** für Personen ohne Zugang (mit E-Mail-Bestätigung wie unten beschrieben).
- Die Person ist über den Code bekannt: Name, Kompetenzen und frühere Bewerbungen sind vorbelegt.
- Der Code wird später zur **Kontoaktivierung** (Passwort setzen, Zwei-Faktor einrichten), ohne neue Registrierung.

## 2 Entscheidungen

| Frage | Entscheidung / Annahme |
|---|---|
| Hauptweg | **Persönlicher Zugang:** Link `…/z/{token}` (Token 32 Zeichen) **und** kurzer Code `K7QM-4XWD` (10 Zeichen aus eindeutigem Alphabet, ca. 50 Bit) zum Eintippen auf `…/zugang`. Beide nur als Hash gespeichert, Code mit Fehlversuchsdrosselung (5 Fehlversuche je Adresse und Stunde, danach 1 Stunde Sperre). |
| Zweitweg | Je Seminar ein **allgemeiner Link** `…/team/bewerbung/{token}` für Personen ohne Zugang (mit E-Mail-Bestätigung). Standard **aus**, einschaltbar je Seminar durch die Koordination. |
| Wer bekommt ihn? | Die Koordination verschickt ihn über Mitteilungen an Teamende oder gibt ihn weiter. Er steht auf der Seminarseite zum Kopieren und als QR-Code. |
| Daten der Bewerbenden | Name, E-Mail (bestätigt), Telefon (optional), Alter (Jahrgang), Kompetenzen (Auswahl wie im Teamprofil), Erfahrung (kurz), Führungswunsch, Nachricht. **Keine Gesundheitsdaten, kein Geburtsdatum, keine Adresse.** |
| Bestätigung der Adresse | **Double-Opt-in**: Die Bewerbung zählt erst nach Klick auf den Bestätigungslink (verhindert fremde Adressen und Spam). |
| Sichtbarkeit am allgemeinen Link | Titel, Leistungsart, Zeitraum, Ort grob (Stadt), Bedarf, Stichtag. **Nicht**: Schulname, Straße, Ansprechperson, **Honorar**. |
| Sichtbarkeit nach persönlichem Zugang | zusätzlich Honorar-Überblick (wie im späteren Teamkonto), Schulname grob. |
| Teamkonten später | Der persönliche Zugang wird zur **Kontoaktivierung**. Bewerbungen laufen weiter unter derselben Person (`person_id`), ohne Übertragung. Bewerben mit dem Zugang bleibt auch danach möglich. |
| Gemeinsame Information | Einmal pro Seminar „Team informieren“: eine Mail pro Person mit Seminar, Terminen, Ort, Treffpunkt, **Team-Übersicht mit Namen und Kontaktdaten** (nur bei Einwilligung der Person), Hinweise der Koordination. |

## 3 Datenmodell

**`seminar_team_invites`**
| Spalte | Typ | Hinweis |
|---|---|---|
| id | bigint | |
| seminar_id | FK | |
| token_hash | string(64) | SHA-256 des Tokens, Index |
| created_by_user_id | FK | |
| expires_at | datetime null | Standard: Stichtag + 1 Tag |
| revoked_at | datetime null | |
| uses | uint | Anzahl bestätigter Bewerbungen |

**`seminar_applications` (erweitert)**
- `user_id` wird **nullable**.
- `guest_name` string(150), `guest_email` string(190), `guest_phone` string(50), `guest_birth_year` smallint null, `guest_competences` json, `guest_experience` text(800), `guest_consent_contact` bool (Kontaktdaten im Team weitergeben), `guest_token_hash` string(64) (für Rückzug und Änderung), `confirmed_at` datetime null (E-Mail bestätigt), `claimed_user_id` FK null, `invite_id` FK null.
- Index `(seminar_id, guest_email)`; Eindeutigkeit je Seminar über Anwendungslogik (eine aktive Bewerbung je Adresse, case-insensitiv; MySQL ist es ohnehin).
- Prüfung per Check in der Anwendung: entweder `user_id` oder `guest_email` gesetzt.

## 4 Arbeitspakete

### C0 Import der Mailingliste und persönlicher Zugang (4 ET)
**Ziel:** Bestehende Mailingliste importieren, jede Person erhält einen persönlichen Zugang.

**Datenmodell**
- Vorhanden: `people` (Name, E-Mail, Telefon …, `email` eindeutig) und `mail_unsubscribes`/`mail_suppressions` (Abmeldungen, Sperren).
- Neu: `people.contact_type` (z. B. `team_contact`) und `people.import_batch_id`.
- Neu: `access_credentials`: `id`, `person_id` (FK), `token_hash` (SHA-256, Index), `code_hash` (SHA-256), `code_hint` (letzte 2 Zeichen, zur Rückfrage am Telefon), `created_at`, `last_used_at`, `revoked_at`, `expires_at` null, `sent_at` null, `uses` uint.
- Neu: `import_batches`: `id`, `kind` (`mailing_list`), `file_name`, `created_by_user_id`, `rows_total`, `rows_new`, `rows_updated`, `rows_skipped`, `rows_error`, `created_at`, `finished_at`.
- Neu: `import_rows` (nur während der Prüfung, 14 Tage): `batch_id`, `line`, `data` json, `status`, `message`.

**Ablauf der Oberfläche** (Bereich Geschäftsstelle, Recht `people.import`: Geschäftsstelle und Vorstand):
1. **Datei hochladen** (CSV, UTF-8 oder Windows-1252, Trennzeichen `;`/`,` – die Schulen-Import-Logik `PartnerImport` ist Vorlage). Spalten: `E-Mail` (Pflicht), `Vorname`, `Nachname`, `Telefon`, optional `Kompetenzen`, `Bundesland`, `Anmerkung`. Eine Mustervorlage zum Download.
2. **Vorschau, nichts wird gespeichert:** je Zeile neu, vorhanden (wird ergänzt), doppelt in der Datei, ungültige Adresse, Abgemeldet/Gesperrt (wird übernommen, aber **nicht angeschrieben**). Zusammenfassung mit Zahlen; Fehlerliste mit Zeilennummer.
3. **Import bestätigen** (alles oder nichts, wie beim Schulen-Import und bei Veranstaltungslisten): legt `people` an bzw. ergänzt leere Felder (überschreibt nie vorhandene Daten) und erzeugt je Person einen Zugang (`access_credentials`).
4. **Zugänge versenden:** über das Rundschreiben (Gruppe „Importierte Kontakte“ als Verteiler, Vorlage „Ihr persönlicher Zugang“ mit den Platzhaltern **`{zugangslink}`** und **`{zugangscode}`**). Die Platzhalter sind je Empfänger eindeutig; der Klartext von Link und Code wird **nur im Versandmoment erzeugt**, danach ist nur der Hash gespeichert. Erneutes Senden erzeugt neue Werte (alte gelten 14 Tage weiter oder werden sofort gesperrt, einstellbar).
5. **Liste der Zugänge:** je Person Status (gesendet, benutzt, gesperrt), Neu senden, Sperren, Code vergeben (für Telefon: Zugang neu erzeugen und einmal anzeigen).

**Datenschutz und Rechtsgrundlage**
- Vor dem ersten Versand klären (Frage R1): Wie sind die Adressen der Liste entstanden, liegt eine Einwilligung zur E-Mail-Kommunikation vor? Der Importdialog zeigt dazu einen Pflichthinweis und protokolliert die Bestätigung der Geschäftsstelle (Datum, Person, kein Listeninhalt).
- Abgemeldete/Gesperrte werden nie angeschrieben; Abmelden bleibt möglich (Rundschreiben-Mechanik).
- Aufbewahrung: Kontakte ohne Aktivität und ohne Konto werden nach 24 Monaten ohne Nutzung des Zugangs zur Löschung vorgeschlagen (Aufgabenübersicht, kein automatisches Löschen).
- Protokoll: „Import durchgeführt“ nur mit Zahlen.

**Sicherheit des Zugangs**
- Token und Code als Hash; Vergleich mit `hash_equals`; Drosselung je Adresse und je Code; kein Hinweis, ob ein Code existiert (immer „Code oder Link nicht gültig“).
- Sitzung nach Zugang: kurzlebige Sitzung (30 Minuten Leerlauf), nur für Gast-Funktionen (Bewerbung, Antrag, Profil lesen/ändern der eigenen Kontaktdaten). Kein Zugriff auf Bereiche des Portals.
- Erneuern/Sperren durch Geschäftsstelle; Person kann sich den Zugang per Adresse neu schicken lassen (neutrale Antwort wie beim Passwort-Zurücksetzen).

**Tests** (`ImportAccessTest`): Vorschau schreibt nichts; alles oder nichts bei Fehlern; Duplikate (Datei und Bestand, unabhängig von Groß/Kleinschreibung); Abgemeldete werden nicht angeschrieben; Code funktioniert einmal pro Hash, falscher Code wird gedrosselt; Klartext nirgends gespeichert; Platzhalter je Person verschieden; Rechte (`people.import`); Sperren/Erneuern; Zugang öffnet keine Portalbereiche.

### C1 Umbau auf „Bewerber“ als gemeinsame Sicht (2 ET)
Ziel: Alle Dienste arbeiten mit einer Sicht auf Bewerbende, ob Konto oder Gast.
- Value Object `Applicant` (name, email, isGuest, userId, profile-Daten). `SeminarApplication::applicant()` liefert es: bei Konto aus `User`/`TeamProfile`, bei Gast aus den `guest_*`-Feldern.
- `TeamApplications::review()`, `suggest()`, `decide()` und `send()` greifen über `applicant()` zu. Beim Gast gehen Mails an `guest_email`, nicht über `User`.
- `conflicts()` (Überschneidung mit bestätigten Seminaren) für Gäste über die Adresse: findet bestätigte Seminare anderer Bewerbungen derselben Adresse.
- Vorschlag (`suggest`): Gäste zählen mit Kompetenzen und Erfahrung; Alter/Geschlecht bleiben wie bisher außen vor (Regel: nicht in die Auswahl einbeziehen).
- Tests anpassen: Bestandstests (Konto) bleiben grün (`TeamApplicationsTest`), neue Tests für Gäste.

### C2 Link-Verwaltung durch die Koordination (1 ET)
- Bereich Koordination, Seminarseite, Abschnitt „Team gesucht“: Anzeige des Links (nach Erzeugen), Schaltflächen **Link erzeugen / erneuern / sperren**, QR-Code (serverseitig als SVG, Bibliothek `bacon/bacon-qr-code` oder ein kleiner eigener Generator; Prüfung der Hosting-Verträglichkeit, keine Bild-Erweiterung nötig).
- Anzeige: gültig bis, Zahl bestätigter Bewerbungen, Hinweis „Link gilt nur bis zum Stichtag“.
- Rechte `team.invites` (Koordination, Geschäftsstelle, Vorstand). Protokoll: „Bewerbungslink erzeugt/gesperrt“ nur mit Seminarnummer.
- Platzhalter `{bewerbungslink}` für Teamende-Mitteilungen (Paket H nutzt ihn in der Vorlage „Team-Ausschreibung“).

### C3 Öffentliche Bewerbung (2 ET)
**Persönlicher Zugang (Hauptweg):** `GET /z/{token}` und `GET|POST /zugang` (Code) öffnen die Seite „Offene Seminare für Sie“ (Liste der Seminare mit offener Bewerbung, Bewerben mit einem Klick, Rückzug, eigene Bewerbungen). Bewerbung ohne E-Mail-Bestätigung; Profil (Name, Telefon, Kompetenzen, Erfahrung, Führungswunsch) ist vorbelegt und änderbar. Honorar-Überblick sichtbar.

**Allgemeiner Link (Zweitweg):**
Routen (ohne Anmeldung, `throttle:20,1`):
- `GET /team/bewerbung/{token}` – Seminarinfo und Formular, wenn Token gültig, Seminar offen (`team_needed`, Stichtag nicht vorbei, Status `scheduled|planning`). Sonst freundliche Fehlerseite („Der Link gilt nicht mehr“), ohne Hinweis, ob Seminar existiert.
- `POST /team/bewerbung/{token}` – prüft Formularschutz, validiert, legt die Bewerbung als `applied` mit `confirmed_at = null` an, sendet **Bestätigungsmail** mit signiertem Link (24 Stunden).
- `GET /team/bewerbung/bestaetigen/{id}` (signiert) – setzt `confirmed_at`, zeigt Dank, nennt Stichtag und Rückzugslink.
- `GET|POST /team/bewerbung/zurueckziehen/{guestToken}` – zieht zurück (Status `withdrawn`), geschützt durch `guest_token_hash`.
- Antworten bei bereits vorhandener Adresse **neutral** (wie bei Veranstaltungsanmeldung): „Wir haben Ihnen eine Mail geschickt.“

Formularinhalt: Name, E-Mail, Telefon (optional), Jahrgang (Auswahlfeld), Kompetenzen (Mehrfachauswahl, gleiche Liste wie `TeamProfile`), Erfahrung (Textfeld mit Zähler), „Ich möchte gern leiten“, Einwilligung 1 (Datenverarbeitung zum Zweck der Teambildung, Pflicht), Einwilligung 2 (Kontaktdaten an das Team weitergeben, optional), Datenschutzhinweis mit Aufbewahrung.

Barrierefreiheit: eine Spalte, Fehlerzusammenfassung, mobil nutzbar (viele bewerben sich vom Handy).

### C4 Koordination arbeitet mit Gästen (0,75 ET)
- Bewerberliste, Zusammensetzung, Vorschlag und Entscheidung zeigen Gäste mit Kennzeichen „Link“ und „E-Mail bestätigt/offen“. **Unbestätigte Gäste zählen nicht** und können nicht ausgewählt werden.
- Entscheidungsmails (`confirmed`, `declined`) an `guest_email`, Text ohne Kontoverweis; enthält bei Bestätigung die Info „Sie bekommen später ein Teamkonto“ (konfigurierbarer Satz).
- Notizen der Koordination (`internal_note`) wie bisher.
- Löschen einzelner Gastbewerbungen auf Wunsch (Art. 17) durch Koordination.

### C5 Gemeinsame Teaminformation (1 ET)
Schaltfläche „Team informieren“ auf der Seminarseite, sobald mindestens eine Person bestätigt ist.
- Seite zeigt Vorschau: Empfänger (Konto und Gast, bestätigt), Betreff aus Vorlage „Teambestätigung“ (Paket H), Text mit Platzhaltern `{seminar}`, `{seminar_nr}`, `{termine}`, `{ort}`, `{schule}`, **`{team}`** (Liste Vor- und Nachname, E-Mail/Telefon nur bei Einwilligung) und freiem Zusatztext der Koordination.
- Versand über den vorhandenen Dienst `Mailings::queue()` (jede Adresse einzeln, Fortschritt, Wiederholung). Für Gäste nutzt er die `e:`-Marken aus Update 8.
- Einmal pro Auswahlrunde; erneuter Versand nach Änderung des Teams mit Hinweis „Team geändert“.
- Ergebnis wird im Seminarverlauf vermerkt (nur Zahl).

### C6 Kontoaktivierung über den Zugang (0,5 ET)
Bei Einführung der Teamkonten: Schaltfläche „Konto aktivieren“ auf der Zugangsseite (Passwort setzen, Zwei-Faktor einrichten). Die Person (`person_id`) bleibt, es entsteht ein `User`; Bewerbungen und Verlauf bleiben der Person zugeordnet. Der Zugang bleibt als Zweitweg bestehen, solange er nicht gesperrt wird.

### C7 Aufräumen und Datenschutz (0,5 ET)
Befehl `seminars:prune-guests`: löscht Gastdaten (Name, E-Mail, Telefon, Erfahrung, Kompetenzen, Tokens) **6 Monate nach Seminarende** oder sofort bei Rückzug nach Fristablauf; Bewerbung bleibt anonymisiert für Zahlen. Eintrag in der Systemsteuerung (Risiko hoch) und Planer (täglich). Löschkonzept in `docs/52_RECHTSTEXTE.md` ergänzen (Datenschutzerklärung: Teambewerbung).

### C8 Tests (im Aufwand enthalten)
`GuestTeamApplicationTest`:
1. Link gültig zeigt Seminarinfo ohne Schulname; ungültig/abgelaufen/gesperrt/Stichtag vorbei zeigt neutrale Seite.
2. Bewerbung legt `applied` an, ohne Bestätigung nicht in der Auswahl.
3. Bestätigungslink (signiert) setzt `confirmed_at`; manipulierter Link 403.
4. Zweite Bewerbung mit derselben Adresse legt keine zweite an, Antwort identisch.
5. Bot-Schutz, Drosselung, Validierung (Name, E-Mail, Einwilligung).
6. Koordination sieht Gäste, entscheidet, Mail geht an Gastadresse.
7. Zusammensetzung/Vorschlag berücksichtigt Gäste (Kompetenzen), ignoriert Alter/Geschlecht.
8. Rückzug über Token.
9. Team informieren: richtige Empfänger (Konto und Gast), Kontaktdaten nur bei Einwilligung, einmalige Zustellung je Adresse.
10. Übernahme beim Anlegen des Kontos.
11. Aufräumen löscht personenbezogene Felder nach der Frist, behält Zahlen.
12. Rechte: nur `team.invites` verwaltet Links.

## 5 Abnahme (Meilenstein M2)
1. Koordination erzeugt Link, sendet ihn per Mitteilung, drei Testpersonen bewerben sich per Handy.
2. Bestätigung per Mail, Bewerberliste zeigt drei, Koordination wählt zwei aus.
3. „Team informieren“: beide bekommen Mail mit Team-Übersicht.
4. Link nach Stichtag gesperrt, Gastdaten nach Frist gelöscht (mit Testdatum).

## 6 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| Link wird weitergegeben | Sperren/erneuern; Bestätigung per Adresse; Koordination entscheidet |
| Mailzustellung (Bestätigung) schlägt fehl | Bewerbung bleibt unbestätigt; Hinweis in der Liste; Zustellfehler über Rundschreiben-Qualität sichtbar |
| Verwechslung Gast/Konto später | Übernahme nur bei bestätigter gleicher Adresse |
| Datenschutz (Kontaktdaten im Team) | nur mit Einwilligung, Standard aus |

## 7 Offene Fragen
Siehe `01_OFFENE_FRAGEN.md`: R1 (Rechtsgrundlage der Mailingliste), R2 (Format der Liste und Zugangsregeln).
