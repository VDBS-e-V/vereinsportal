# Paket C: Teambewerbung per Link ohne Konto und gemeinsame Teaminformation

Aufwand: **8 ET**. Ziel: Teamende bewerben sich über einen **Link ohne Anmeldung** auf ein Seminar, bis die Teamkonten kommen. Nach der Auswahl wird das **ganze Team gemeinsam** informiert.

## 1 Ausgangslage
- `SeminarApplication` verlangt `user_id` (Konto der Rolle Team, Capability `seminars.apply`). Bewerben, Zurückziehen, Entscheidungen, Zusammensetzung (`review`), Vorschlag (`suggest`), Überschneidungswarnung (`conflicts`) und Benachrichtigungen (`send`) arbeiten mit `User`.
- Die Koordination sieht je Seminar die Bewerbungen mit Profil (Alter, Geschlecht, Kompetenzen, Erfahrung) und kann nach dem Stichtag entscheiden.
- Formularschutz (`bot guard`), E-Mail-Bestätigung per signiertem Link (Muster: Veranstaltungsanmeldung, Beitrittsantrag) und die Einzel-Adressen im Rundschreiben (`e:`-Marken) sind vorhanden.

## 2 Entscheidungen

| Frage | Entscheidung / Annahme |
|---|---|
| Was ist der Link? | Je Seminar ein **geheimer Link** `…/team/bewerbung/{token}`; Token zufällig, 40 Zeichen, nur als Hash gespeichert. Erzeugbar, erneuerbar, sperrbar durch die Koordination. |
| Wer bekommt ihn? | Die Koordination verschickt ihn über Mitteilungen an Teamende oder gibt ihn weiter. Er steht auf der Seminarseite zum Kopieren und als QR-Code. |
| Daten der Bewerbenden | Name, E-Mail (bestätigt), Telefon (optional), Alter (Jahrgang), Kompetenzen (Auswahl wie im Teamprofil), Erfahrung (kurz), Führungswunsch, Nachricht. **Keine Gesundheitsdaten, kein Geburtsdatum, keine Adresse.** |
| Bestätigung der Adresse | **Double-Opt-in**: Die Bewerbung zählt erst nach Klick auf den Bestätigungslink (verhindert fremde Adressen und Spam). |
| Sichtbarkeit des Seminars am Link | Titel, Leistungsart, Zeitraum, Ort grob (Stadt), Bedarf, Stichtag. **Nicht**: Schulname, Straße, Ansprechperson, Honorar. |
| Teamkonten später | Bei Anlegen/Einladen eines Kontos mit **gleicher bestätigter Adresse** werden Gastbewerbungen übernommen (`claimed_user_id`), die Link-Bewerbung bleibt in der Liste mit Hinweis „jetzt Konto“. |
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

### C6 Übernahme bei späteren Teamkonten (0,5 ET)
Bei `User`-Anlage/Einladung mit Rolle Team: Dienst `GuestApplicationClaims` sucht bestätigte Gastbewerbungen mit gleicher Adresse und setzt `user_id`/`claimed_user_id`. Kein Datenverlust, Verlauf bleibt.

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
1. Welche Kompetenzen stehen zur Auswahl (Liste aus `TeamProfile` übernehmen)?
2. Sollen Gäste Alter angeben (nur Jahrgang)? Aktuell nur für die Zusammensetzung sichtbar.
3. Wann kommen die Teamkonten ungefähr (Dauer des Linkwegs)?
4. Honorar am Link zeigen oder nicht (Vorschlag: nicht)?
