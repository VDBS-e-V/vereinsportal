# 67 Projektplan: Lücken gegenüber der Anforderungsliste

Stand: Plan, noch nichts davon gebaut. Grundlage ist eine Prüfung des Codes (Module, Migrationen, Routen) gegen die Liste des Vorstands. Aufwände sind grobe Schätzungen in Entwicklungstagen (ET) einer Person.

Zeichen: ✓ abgedeckt, ◐ teilweise, ✗ fehlt.

## 1 Zielumgebung und was sie ändert

Ziel ist der Betrieb auf **normalem Webhosting**: keine VM, **kein Konsolenzugang, nur FTP**. Das Projekt ist bisher für eine VM mit Konsole geplant (`docs/05`, `docs/27`, `docs/28`: Nginx, Cron, systemd, `shared/storage`, `artisan` per SSH). Folgen:

| Annahme im Projekt heute | Auf dem Webhosting |
|---|---|
| `php artisan migrate`, `passport:keys`, `role:grant-administration` per Konsole | Nicht möglich. Es gibt die Systemsteuerung (`/admins/system`, Migrationen und Zwischenspeicher per Knopf), aber keine Erstinstallation und keine Passport-Schlüssel ohne Konsole. |
| `composer install`, `npm run build` auf dem Server | Muss lokal laufen. Hochgeladen wird ein fertiges Paket mit `vendor/` und `public/build`. |
| Worker `queue:work` als Dienst | Nicht möglich. Aufträge müssen von einem Cron-Aufruf abgearbeitet werden. |
| Cron `schedule:run` jede Minute | Hängt vom Hoster: oft Cron im Kundenmenü (dann ohne Konsole machbar), manchmal nur alle 5–15 Minuten oder gar nicht. Fallback: Web-Cron-Adresse, die ein externer Dienst aufruft. |
| PHP 8.4.1 (`composer.json`) | Muss der Hoster anbieten. Das ist die erste Frage an den Hoster (siehe Abschnitt 5). |
| `storage:link`, Symlinks, Document-Root auf `public/` | Oft nicht frei wählbar. Das Projekt liefert private Dateien über Controller aus, braucht also keinen Symlink; das Document-Root muss aber geklärt werden. |

## 2 Bestandsaufnahme

### Mail an Verteiler
| Punkt | Stand | Beleg / Lücke |
|---|---|---|
| Versand für Vorstand, Geschäftsstelle, Koordination | ✓ | Rundschreiben an Gruppen (Vorstand, Geschäftsstelle) und Mitteilungen an Teamende zu Seminaren (Koordination), `docs/63_RUNDSCHREIBEN.md` |
| Verschiedene Rechte bei erreichbaren Listen | ✓ | `mailings.members` (nur Vorstand), `mailings.send`, `mailings.team`; Gruppen nach Recht gefiltert (`MailGroups::allowedFor`) |
| Vorlagen für Mails | ✓ | Mailvorlagen mit Versionsverlauf, fester Rahmen für Seminarmitteilungen |
| Umfangreiche Textbausteine | ◐ | Verwaltung, Einfügen per Auswahl und Klick sind da. Es gibt **keinen Grundbestand** an Bausteinen und Vorlagen, keine Kategorien, kein Suchen in der Verwaltung. |
| Verteiler als eigene Listen | ◐ | Gruppen nach Rolle, Ausschlüsse, Einzelpersonen. **Gespeicherte Verteiler/Segmente fehlen** (B5/B7 aus `docs/65`). Die Koordination erreicht nur Teamende zu Seminaren, andere Gruppen nicht: Rechtefrage offen. |

### Seminare anlegen, planen, sichern
| Punkt | Stand | Beleg / Lücke |
|---|---|---|
| Seminare anlegen, Stammdaten | ✓ | `Seminar` mit Titel, Leistung, Schule, Klassenstufe, Teilnehmende, Notiz, Status, Verlauf |
| **Seminarnummer** | ✗ | Keine Nummer außer der technischen ID. Schulen haben eine interne Kennung, Seminare nicht. |
| Planung: Tage, Ort | ✓ | Termine (`SeminarSession`) mit Beginn, Ende, Ort; Kostenvoranschläge mit Versionen |
| Team suchen und zusammenstellen | ✓ | Bedarf, Stichtag, Kompetenzen, Bewerbungen, Teamzusammensetzung, Vorschlag, Überschneidungswarnung |
| Nach der Auswahl alle gemeinsam benachrichtigen | ◐ | Entscheidungen gehen einzeln an Bewerbende; eine gemeinsame Nachricht an das bestätigte Team geht über die Teammitteilungen. Eine **Teamübersicht-Mail mit allen Namen und Kontakten** gibt es nicht. |
| **Bewerbung für das Team per Link ohne Konto** | ✗ | Bewerben setzt ein Konto mit Rolle Team voraus (`seminars.apply`). |
| **Sicherung: Dokumente, Statistiken, Ergebnisse** | ✗ | Es gibt keine Dokumentablage je Seminar. Vorhanden: Kostenvoranschlag als Druckansicht, Anwesenheitsexport bei Veranstaltungen. |
| **Digitale Feedbackformulare** (SuS, Lehrkräfte, Teamende) | ✗ | Nur ein Freitext „Rückmeldung“ der Schule (`SchoolRequests::feedback`). Keine Formulare, keine Auswertung, kein Zugang ohne Konto. |

### Login-System / Provider
| Punkt | Stand | Beleg / Lücke |
|---|---|---|
| Eigenes Login mit Zwei-Faktor | ✓ | Anmeldung, 2FA, Konto, Sitzungen |
| SSO / OAuth als Anbieter für andere Systeme | ◐ | Laravel Passport ist eingebunden (`Modules/Sso`, Authorization Code mit PKCE, Laufzeiten). **Es fehlen**: Verwaltung der Clients, OIDC-Erkennung und Userinfo (Name, E-Mail, Rollen), Zustimmungsseite im Portal-Design, Schlüsselerzeugung ohne Konsole, Anleitungen für Nextcloud/Moodle, Tests. Heute nur per `passport:client` auf der Konsole möglich. |
| Anmeldung über fremde Anbieter (Google, Microsoft …) | ✗ | Nicht geplant. Unklar, ob gewünscht (Frage 3). |

### Formulare für die Geschäftsstelle
| Punkt | Stand | Beleg / Lücke |
|---|---|---|
| **Kostenrückerstattung** | ✗ | Kein Antrags- oder Abrechnungsmodul. |
| Weitere Formulare („…“) | ✗ | Nicht erfasst; Liste fehlt (Frage 4). |

### Betrieb
| Punkt | Stand | Lücke |
|---|---|---|
| Live-Betrieb auf Webhosting per FTP | ✗ | Die Betriebsdokumente setzen eine VM und Konsole voraus. Teilweise vorbereitet: Systemsteuerung mit Migrationen und Zwischenspeichern, Zustandsprüfungen, Wartungsmodus. |

## 3 Projektplan

Reihenfolge: **A zuerst** (ohne Betrieb nützt der Rest nichts), dann die Seminarkette **B → C → D → E**, danach F, G, H. Alle Pakete halten die Regeln des Projekts ein: eigener Bereich je Seite, Rechte als Capabilities, Protokoll nur mit Art und Zahlen, Aufbewahrungsfristen mit Aufräum-Befehl, Pest-Tests, Doku in `docs/`.

### Paket A: Betrieb auf Webhosting ohne Konsole (ca. 8 ET)

Ziel: Das Portal läuft nach FTP-Upload, wird per Browser eingerichtet und aktualisiert.

1. **A1 Hoster-Prüfung** (0,5 ET): PHP-Version und -Erweiterungen (`gd`, `intl`, `mbstring`, `pdo_mysql`, `fileinfo`, `openssl`), Document-Root, Cron-Möglichkeit, `max_execution_time`, Mail-Limits pro Stunde, Speicher, MySQL-Version. Ergebnis: ausgefüllte Checkliste in `docs/68_WEBHOSTING.md`.
2. **A2 Release-Paket** (1,5 ET): Skript `scripts/build-release.ps1` (Windows) und `.sh`: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, ZIP ohne `.git`, `tests`, `node_modules`, `docs`. Enthält `public/build` und `vendor/`. Versionsdatei `RELEASE` zur Anzeige in der Systemsteuerung.
3. **A3 Verzeichnislayout** (1 ET): Variante 1: Document-Root auf `public/` einstellbar. Variante 2 (fest `public_html`): Inhalt von `public/` nach `public_html`, Rest eine Ebene darüber; angepasste `index.php` (Pfade) und `.htaccess` (Rewrite, Zugriffsschutz für `.env`). Beides als Anleitung und im Paket als Vorlage.
4. **A4 Einrichtung im Browser** (2 ET): Seite `/einrichtung`, nur erreichbar, solange `storage/app/setup.lock` fehlt und `SETUP_TOKEN` in der `.env` steht. Schritte: Voraussetzungen prüfen, Datenbank testen, `.env` (aus hochgeladener Vorlage) nur lesen, `APP_KEY` erzeugen und in `storage/app/key` ablegen oder anzeigen, Migrationen per `Artisan::call('migrate', ['--force'])`, Seeder für Rollen, ersten Administrationskonto anlegen (ersetzt `role:grant-administration`), Passport-Schlüssel erzeugen (`passport:keys`), Sperre schreiben. Tests: gesperrt ohne Token, gesperrt nach Abschluss, legt Konto mit Rolle an.
5. **A5 Aktualisieren ohne Konsole** (1 ET): Die Systemsteuerung kann Migrationen und Zwischenspeicher bereits. Ergänzen: Anzeige „Datenbank auf altem Stand“ mit Hinweis nach dem Upload, Wartungsmodus automatisch während `migrate`, Sicherungshinweis. Update-Ablauf in der Doku: Wartungsmodus an, FTP-Upload (Reihenfolge `vendor`, `app`, `public`), Migrationen, Zwischenspeicher leeren, Wartungsmodus aus.
6. **A6 Planer und Warteschlange per Cron** (1,5 ET): Befehl `portal:tick` (führt `schedule:run` und `queue:work --stop-when-empty --max-time=45` aus) und **Web-Cron** `GET /cron/{token}` (Token aus `.env`, Drosselung, nur ein Lauf gleichzeitig per Sperre). Anleitung für Cron im Kundenmenü des Hosters oder externen Aufrufdienst. Rundschreiben: Paketgröße und `per_minute` auf das Mail-Limit des Hosters einstellen (vorhanden: `MAILINGS_BATCH_SIZE`, `MAILINGS_PER_MINUTE`). Zustandsprüfung „Planer läuft“ ist vorhanden.
7. **A7 Betriebsdoku ersetzen** (0,5 ET): `docs/05`, `27`, `28` auf den Hosting-Weg anpassen, VM-Weg als Alternative belassen.

Abhängigkeit: Antworten zu A1. Risiko: Hoster ohne Cron und ohne PHP 8.4. Dann Hosterwechsel oder `composer.json` auf 8.3 senken (prüfen, ob Abhängigkeiten das zulassen).

### Paket B: Seminarnummer und Stammdaten (ca. 2 ET)

- Migration: `seminars.number` (string 20, eindeutig, Index), Zähler pro Jahr in Tabelle `seminar_counters` (year, last). Format `S-JJJJ-NNNN` (Annahme, siehe Frage 1). Vergabe beim Anlegen in einer Transaktion mit Sperre; Nachtrag für vorhandene Seminare nach `created_at`.
- Anzeige in Listen, Kopf, Kostenvoranschlag, E-Mails, Protokoll; Suche nach Nummer; Export.
- Weitere Stammdaten nach Ihrer Liste (Frage 1): z. B. Ansprechperson an der Schule, Anzahl Klassen, Anmeldedatum.
- Tests: Nummern lückenlos je Jahr, unveränderlich, Suche, Nachtrag.

### Paket C: Teambewerbung per Link ohne Konto (ca. 6 ET)

- Tabelle `seminar_team_invites` (seminar_id, token_hash, expires_at, revoked_at) und Erweiterung `seminar_applications`: `user_id` wird optional, neue Spalten `guest_name`, `guest_email`, `guest_phone`, `guest_competences`, `guest_experience`, `guest_token_hash` (für spätere Änderung/Rückzug), `claimed_user_id`.
- Öffentliche Seite `/team/bewerbung/{token}`: Seminarinfo (grob, ohne Schule-Adresse), Formular mit Formularschutz (`bot guard` vorhanden), Einwilligung, Bestätigungsmail mit Rückzugslink. Link je Seminar erzeugbar, erneuerbar, sperrbar; Stichtag gilt wie bisher.
- Koordination: bestehende Bewerberliste, Zusammensetzung, Vorschlag und Entscheidung zeigen Gast- und Kontobewerbungen gemeinsam (Quelle als Kennzeichen). Entscheidungsmails gehen an `guest_email`.
- **Gemeinsame Benachrichtigung nach der Auswahl:** Schaltfläche „Team informieren“ sendet eine Mail an alle Bestätigten (Konto und Gast) mit Seminar, Terminen, Ort und den Kontaktdaten aller im Team (Einwilligung nötig). Nutzt die Einzelpersonen-Auswahl der Rundschreiben (`e:`-Marken sind schon da).
- Später: Wenn ein Teamkonto mit gleicher Adresse entsteht, werden Gastbewerbungen übernommen (`claimed_user_id`).
- Datenschutz: Aufbewahrung der Gastdaten nach Seminarabschluss (Annahme 6 Monate, Befehl `seminars:prune-guests`), keine Gesundheitsdaten.
- Tests: Link gültig/abgelaufen/gesperrt, Stichtag, Duplikate je Adresse, gemeinsame Liste, Entscheidung mit Mail, Übernahme beim Kontoanlegen, Aufräumen.

### Paket D: Sicherung des Seminars (ca. 7 ET)

- Tabelle `seminar_documents` (seminar_id, kind, title, original_name, path, mime, size, uploaded_by, created_at). Arten: Vertrag, Kostenvoranschlag, Teilnehmendenliste, Auswertung/Statistik, Ergebnisse, Bericht, Sonstiges.
- Upload in der Seminarseite: PDF, Office-Dokumente, CSV, ZIP; **Bilddateien werden abgelehnt** („keine Fotos von Teilnehmenden“), mit Hinweistext. Größe und Gesamtgrenze je Seminar einstellbar (Hosting-Speicher beachten). Privater Speicher, Download per Controller, Berechtigung `seminars.manage`.
- Automatische Stände: beim Wechsel auf „Abgeschlossen“ legt das Portal einen Schnappschuss ab: Kostenvoranschlag (letzte Version) als druckbares HTML/PDF, Terminliste, Team (Namen, Rollen), Kennzahlen (Teilnehmende, Rückläufe der Feedbackformulare mit Durchschnitten aus Paket E). Statuswechsel „Abgeschlossen“ zeigt eine **Checkliste** (Dokumente vorhanden, Auswertung hochgeladen, Abrechnung erledigt); Überspringen möglich mit Begründung.
- **Seminarmappe:** Download aller Dokumente und des Schnappschusses als ZIP (`ZipArchive`, Erweiterung `zip` prüfen).
- Aufbewahrung: Frist einstellbar (Annahme 10 Jahre für Abrechnungsunterlagen, kürzer für Teamdaten), Befehl `seminars:prune-documents`, Hinweis in der Löschübersicht.
- Tests: Bildablehnung, Berechtigung, Größe, Schnappschuss, ZIP-Inhalt, Aufräumen, Zugriff fremder Rollen.

### Paket E: Digitale Feedbackformulare (ca. 9 ET)

- **Formularbaukasten (klein, wiederverwendbar):** Tabellen `feedback_forms` (key, audience: `students|teachers|team`, title, version, questions JSON, active), `feedback_links` (seminar_id, form_id, token_hash, opens_at, closes_at, max_responses), `feedback_responses` (link_id, submitted_at, answers JSON; **keine IP, keine Namen**). Fragetypen: Skala 1–5, Ja/Nein, Auswahl, Mehrfachauswahl, Freitext. Standardformulare für die drei Zielgruppen werden mitgeliefert (Inhalt mit Ihnen abstimmen, Frage 5).
- **Zugang ohne Konto:** Link und QR-Code je Seminar und Zielgruppe (SuS im Unterricht, Lehrkräfte per Mail, Teamende per Mail). Mobil bedienbar, einfache Sprache, Formularschutz, Drosselung je Link, Schließen per Datum.
- **Verwaltung (Koordination, Vorstand):** Formulare bearbeiten (Version bei Änderung, alte Antworten behalten ihre Version), Links erzeugen, QR-Druckansicht, Rücklaufzähler, **Auswertung** je Seminar und gesamt: Durchschnitte, Verteilungen als Tabelle, Freitexte lesen, Export CSV. Auswertung erst ab Mindestanzahl Antworten (Annahme 5), damit einzelne Personen nicht erkennbar sind.
- Teamende-Feedback wahlweise mit Namen (freiwillig).
- Mail an Lehrkraft/Team mit dem Link (Rundschreibenfunktion mit Platzhalter `{feedback_link}`).
- Datenschutz: SuS sind Minderjährige, daher keine personenbezogenen Daten; Aufbewahrung der Rohantworten kurz (Annahme 2 Jahre), Kennzahlen bleiben im Seminar-Schnappschuss. Datenschutzhinweis auf dem Formular.
- Tests: Antwort ohne Konto, Schließen, Mindestanzahl, Versionen, kein Speichern von IP/Namen, Export, Rechte.

### Paket F: SSO / OAuth ausbauen (ca. 6 ET)

1. **F1 Schlüssel und Clients ohne Konsole** (1,5 ET): Bei der Einrichtung (A4) `passport:keys`; Verwaltungsseite im Bereich Admins „Anbindungen“: Client anlegen (Name, Redirect-Adressen, vertraulich ja/nein), Geheimnis **einmal** anzeigen, Sperren/Löschen, Protokoll ohne Geheimnis. Berechtigung nur Administration (`system.manage`).
2. **F2 OIDC** (2 ET): Discovery `/.well-known/openid-configuration`, JWKS aus dem Passport-Schlüssel, ID-Token (Passport 13 mit OIDC-Erweiterung oder eigene Antwort), `userinfo` mit Scopes `openid profile email roles`; Claims aus Konto und Rollen (keine Geburtsdaten, keine Adresse).
3. **F3 Zustimmung und Abmeldung** (1 ET): Zustimmungsseite im Portal-Design mit Klartext zu den Scopes; „Verbundene Anwendungen“ in den Kontoeinstellungen mit Widerruf; Single-Logout (Weiterleitung), soweit der Client es unterstützt.
4. **F4 Anleitungen und Tests** (1,5 ET): Einrichtung für Nextcloud und Moodle (Schritt für Schritt), Ende-zu-Ende-Test des Code-Flows mit PKCE, abgelaufene Tokens, gesperrte Konten (Konto gesperrt = Token ungültig).
5. **F5 (nur falls gewünscht) Anmeldung über fremde Anbieter** (3 ET zusätzlich): Laravel Socialite mit Google/Microsoft, Verknüpfung nur zu bestehenden Konten über bestätigte Adresse, kein automatisches Anlegen. Entscheidung Frage 3.

### Paket G: Formulare für die Geschäftsstelle (ca. 10 ET)

Zuerst die **Kostenrückerstattung**, danach weitere Formulare auf demselben Ablauf.

- Gemeinsamer Ablauf „Vorgänge“: Status `eingereicht → in Prüfung → freigegeben → ausgezahlt` oder `abgelehnt/zurückgegeben` mit Begründung; Vier-Augen-Freigabe ab einem Betrag (analog `mailings.approve`); Verlauf; Benachrichtigungen; Aufbewahrung.
- **Kostenrückerstattung:** Antragstellende sind Konten (Team, Mitglieder) oder, bis Teamkonten da sind, Personen per Link (Wiederverwendung aus Paket C). Felder: Anlass (Seminar-Nummer aus Paket B oder Freitext), Positionen (Datum, Art: Fahrt/Unterkunft/Verpflegung/Material/Sonstiges, Beschreibung, Betrag, bei Fahrt Strecke und Kilometersatz), Belege hochladen (PDF/JPEG/PNG, Pflicht ab Betrag), Kontoverbindung (IBAN geprüft, **verschlüsselt gespeichert**, nach Auszahlung gelöscht), Erklärung. Summenprüfung, Kilometersatz aus der Preisverwaltung.
- Geschäftsstelle: Eingangsliste mit Filter, Prüfseite mit Belegen, Rückfrage per Mail, Freigabe, „Ausgezahlt“ mit Datum, **Sammelexport** für die Buchhaltung (CSV, später SEPA-Überweisungsdatei).
- Rechte: `expenses.submit` (Konten), `expenses.manage` (Geschäftsstelle), `expenses.approve` (Vorstand).
- **Weitere Formulare** (Honorarabrechnung, Fahrtkostenabrechnung, Materialbestellung, Urlaubs-/Abwesenheitsmeldung …): Nach Ihrer Liste (Frage 4). Wenn die Formulare nur Felder, Belege und eine Freigabe brauchen, entsteht ein einfacher **Formular-Baukasten** auf dem Fragetypen-Baustein aus Paket E (Aufwand ca. 4 ET zusätzlich), sonst je Formular ein eigener Typ (ca. 3 ET).
- Tests: Summen, Belegpflicht, Verschlüsselung und Löschung der IBAN, Statusfolge, Vier-Augen, Export, Rechte.

### Paket H: Verteiler und Textbausteine (ca. 5 ET)

- **Gespeicherte Verteiler:** Tabelle `mail_segments` (name, definition JSON, owner area), Definition aus Gruppen, Ausschlüssen und Filtern (Bundesland der Schule, Einarbeitung offen, Mitglied seit, Seminar-Teamende). Beim Versand neu berechnet. Sichtbarkeit je Rolle (Rechte-Matrix, Frage 2).
- **Rechte-Matrix:** welche Rolle welche Verteiler sieht und nutzt, Koordination eingeschlossen. Als Tabelle in der Doku und als Test.
- **Grundbestand an Bausteinen und Vorlagen:** mit der Migration mitgeliefert, danach bearbeitbar: Einladung Mitgliederversammlung, Erinnerung, Absage, Seminarbestätigung an Schule, Team-Ausschreibung, Teambestätigung, Feedback-Bitte, Datenschutzhinweis, Kontaktblock, Terminblock, Abmeldehinweis. Kategorien und Suche in der Verwaltung.
- Tests: Verteilerberechnung zum Sendezeitpunkt, Sichtbarkeit, Grundbestand vorhanden.

## 4 Zeitplan und Meilensteine

| Meilenstein | Pakete | Ergebnis | ET (kumuliert) |
|---|---|---|---|
| M1 Live-Fähigkeit | A | Portal läuft auf dem Hosting, Einrichtung und Updates ohne Konsole | 8 |
| M2 Seminarkette | B, C | Nummer, Teambewerbung per Link, gemeinsame Teaminformation | 16 |
| M3 Sicherung und Feedback | D, E | Seminarmappe, Feedbackformulare mit Auswertung | 32 |
| M4 Anbindungen | F | SSO für Nextcloud/Moodle, Verwaltung ohne Konsole | 38 |
| M5 Geschäftsstelle | G, H | Kostenrückerstattung, Verteiler, Bausteine | 53 |

Die Zahlen sind Schätzungen mit ±30 %. M1 hat Vorrang, weil die Livestellung davon abhängt. Danach lässt sich jeder Meilenstein einzeln ausliefern; jede Auslieferung bekommt Pest-Tests, Doku und eine Anleitung in einfacher Sprache.

## 5 Offene Fragen (bitte beantworten, sonst gelten die Annahmen)

1. **Seminarnummer:** Format `S-JJJJ-NNNN` in Ordnung? Welche weiteren Stammdaten braucht ein Seminar?
2. **Verteiler:** Soll die Koordination auch andere Gruppen als Teamende anschreiben dürfen? Welche Verteiler sieht wer?
3. **Anmeldung über fremde Anbieter** (Google, Microsoft): gewünscht oder nur das Portal als Anbieter für Nextcloud/Moodle?
4. **Formulare der Geschäftsstelle:** Welche außer der Kostenrückerstattung? Wer genehmigt ab welchem Betrag? Welche Daten braucht die Buchhaltung (DATEV, CSV)?
5. **Feedback:** Fragen für SuS, Lehrkräfte und Teamende liefern Sie oder ich entwerfe sie aus gängigen Evaluationsbögen? Mindestanzahl Antworten für die Auswertung?
6. **Aufbewahrung:** Fristen für Seminarunterlagen, Gastbewerbungen, Feedbackantworten, Belege (steuerliche Aufbewahrung beachten, Vorstand/Steuerberatung).
7. **Hoster:** PHP-Version, Cron, Document-Root, Mail-Limit, Speicher. Name des Anbieters hilft; ich passe Paket A an.
8. **Teamkonten „später“:** Wann ungefähr? Davon hängt ab, wie lange der Linkweg (Paket C) tragen muss.

## 6 Risiken

| Risiko | Folge | Gegenmaßnahme |
|---|---|---|
| Hoster ohne PHP 8.4 oder ohne Cron | Betrieb nicht möglich oder verzögert | Frage 7 sofort klären, Web-Cron als Ausweg, Hosterwechsel einplanen |
| Kein Queue-Worker | Große Rundschreiben laufen lang | Cron-getriebene Abarbeitung in kleinen Paketen (A6) |
| Fehler beim FTP-Upload (Teilstand) | Seiten kaputt | Wartungsmodus vor dem Upload, feste Reihenfolge, Prüfsummendatei im Paket |
| Feedback von Minderjährigen | Datenschutz | Keine personenbezogenen Daten, keine IP, Mindestanzahl |
| IBAN und Belege | Datenschutz, Aufbewahrung | Verschlüsselung, Löschen nach Auszahlung, Belege nur Berechtigten |
| Passport-Schlüssel im Dateisystem | Verlust bricht SSO | Sicherung zusammen mit `.env`, Hinweis in der Betriebsdoku |
| Lange Pakete ohne Praxistest | Fehlentwicklung | Nach M2 und M3 je ein Probelauf mit der Koordination |
