# Checkliste: Probelauf auf dem Strato-Hosting

Zweck: Das Portal einmal vollständig auf dem echten Hosting aufbauen und durchspielen, bevor es genutzt wird. Grundlage sind `68_WEBHOSTING.md` (Einzelheiten), `29_BACKUP_RESTORE_RUNBOOK.md` und `30_STAGING_PREFLIGHT_AND_SMOKE_TEST.md`. Nehmen Sie dafür **eine Test-Domain oder Subdomain** und eine **eigene Datenbank**, nicht die spätere echte Adresse. Planen Sie einen halben Tag ein.

So nutzen Sie die Liste: Haken setzen, bei jeder Abweichung die Beobachtung notieren (was, wo, Fehlermeldung). Schicken Sie mir die Notizen, ich ordne sie ein.

## A. Vorbereitung im Strato-Kundenmenü
- [ ] PHP-Version der Domain auf 8.4 (oder höher) gestellt.
- [ ] Erweiterungen vorhanden: `zip`, `gd`, `intl`, `bcmath`, `exif`, `sodium`. (Prüfen mit `scripts/hostcheck.php`; `sodium` ist für die Anmeldung bei Nextcloud/Moodle nötig.)
- [ ] Datenbank angelegt; Name, Benutzer, Passwort, Host notiert.
- [ ] Domain angelegt, **Document-Root zeigt auf `…/public`**.
- [ ] HTTPS-Zertifikat aktiv, Weiterleitung auf HTTPS an.
- [ ] Postfach für den Mailversand (SMTP-Zugang) angelegt.
- [ ] Konto bei cron-job.org angelegt.

## B. Paket bauen und hochladen
- [ ] Auf Ihrem Rechner: `.\scripts\build-release.ps1`. Es entsteht `build\portal-<Version>.zip` und `release-manifest.json`.
- [ ] Per FTP hochgeladen: zuerst `vendor/`, dann `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `public/`, zuletzt `RELEASE` und `release-manifest.json`.
- [ ] `deploy/env.strato.example` als `.env` ausgefüllt hochgeladen: `APP_URL` (mit https), Datenbank, SMTP, `SETUP_TOKEN`, `CRON_KEY` (zufällige lange Zeichenfolgen), `MAIL_FROM_ADDRESS`.
- [ ] Schreibrechte für `storage/` und `bootstrap/cache/` (samt Unterordnern).
- [ ] **Sicherheitsprobe:** `https://DOMAIN/.env` und `https://DOMAIN/storage/` im Browser öffnen. Beides muss **nicht** erreichbar sein (404 oder 403). Sonst sofort Document-Root korrigieren.

## C. Einrichtung im Browser
- [ ] `https://DOMAIN/einrichtung` mit dem `SETUP_TOKEN` geöffnet, Voraussetzungen grün.
- [ ] `APP_KEY` eingetragen (falls gefordert), Datenbank angelegt, erste Administration angelegt.
- [ ] Abschluss ausgeführt (`/einrichtung` antwortet danach mit 404).
- [ ] `SETUP_TOKEN` aus der `.env` entfernt und die Datei neu hochgeladen.
- [ ] Angemeldet, Zwei-Faktor für die Administration eingerichtet.

## D. Planer und Warteschlange (Cron)
- [ ] Bei cron-job.org Aufruf `https://DOMAIN/cron/<CRON_KEY>`, GET, jede Minute, Zeitüberschreitung 60 s.
- [ ] Admins › System › Zustand: „Cron-Aufruf“ und „Planer“ zeigen ein Alter unter 2 Minuten.
- [ ] Admins › System › Aufgaben: **„Standardinhalte laden“** ausgeführt (Mailvorlagen und Textbausteine).

## E. Mail
- [ ] Admins › System › Einstellungen: **Testmail** gesendet und angekommen (Posteingang, nicht Spam).
- [ ] Rundschreiben › Zustellqualität › **Absenderprüfung** (SPF, DKIM, DMARC) angesehen; Fehlendes beim Hoster ergänzt.
- [ ] Rundschreiben an **zwei eigene Adressen** gesendet: kommt an, Abmeldelink funktioniert, Sperrliste wirkt.

## F. Funktionen durchspielen (Testdaten, nichts Echtes)
Legen Sie dafür kleine Testkonten an (`portal:demo-users` läuft nur lokal; auf dem Hosting Konten über Geschäftsstelle › Personen & Zugang anlegen).
- [ ] **Mitgliedschaft:** Antrag über die öffentliche Seite, Bestätigungslink per Mail, Vorstand nimmt auf.
- [ ] **Schule und Seminar:** Schule anlegen, Seminar mit Terminen und Kostenvoranschlag, Teamende bewerben sich, Koordination bestätigt.
- [ ] **Verträge:** Seminar › Abschnitt „Verträge“, Verträge erzeugen, **PDF herunterladen und ansehen** (Seitenumbruch, Unterschriftslinien, Fußzeile, Umlaute), Scan hochladen, Abschlussliste zeigt „Verträge sind unterschrieben“.
- [ ] **Seminar-Dokumente:** „Aus dem Portal erzeugen“ als PDF.
- [ ] **Feedback:** Link für ein Seminar erzeugen, **Plakat drucken und QR-Code mit dem Handy scannen**, Antwort abgeben.
- [ ] **Anträge:** Auslagen mit Beleg (PDF/JPEG) und Fahrtkosten mit PKW (20 Cent je km, höchstens 130 €), Prüfung und Freigabe, Auszahlungsliste exportieren (Excel/CSV öffnet ohne Fehler).
- [ ] **Bestellung → Erstattung:** Bestellung freigeben, „Aus eigener Tasche bezahlt“ erzeugt den Entwurf.
- [ ] **Verteiler:** Verteiler mit Filter anlegen, Vorschau zeigt die erwartete Zahl, Rundschreiben daran senden.
- [ ] **Datenschutz-Seite:** `/datenschutz` zeigt den Entwurf; Platzhalter in eckigen Klammern sind noch zu füllen.

## G. Dateien und Uploads
- [ ] Beleg mit 5 MB und ein größerer Scan hochladen (Limits des Hosters: `upload_max_filesize`, `post_max_size`).
- [ ] Hochgeladene Dateien sind **nicht** direkt per Adresse abrufbar, sondern nur über das Portal (eingeloggt).

## H. Anbindung Nextcloud/Moodle (nur wenn schon vorhanden)
- [ ] Admins › Anbindungen: Schlüssel vorhanden, Anbindung angelegt (Name, Umleitungsadresse, erlaubte Rollen).
- [ ] In Nextcloud: App „OpenID Connect user backend“ eingerichtet (siehe `72_SSO_ANBINDUNG.md`).
- [ ] Testkonto mit erlaubter Rolle meldet sich über das Portal an und landet in den richtigen Gruppen.
- [ ] Testkonto **ohne** erlaubte Rolle wird abgewiesen.
- [ ] Konto › Verbundene Anwendungen zeigt die Anbindung und lässt sich widerrufen.

## I. Sicherung und Wiederherstellung
- [ ] Datenbankexport im Kundenmenü erstellt.
- [ ] Per FTP gesichert: `.env`, `storage/oauth-private.key`, `storage/oauth-public.key`, `storage/app/private`.
- [ ] **Wiederherstellung einmal geübt** (Export in eine zweite Test-Datenbank einspielen, Portal dagegen starten). Ohne denselben `APP_KEY` sind verschlüsselte Daten (IBAN, Steuernummer, Zwei-Faktor) unlesbar.

## J. Aktualisieren üben
- [ ] Kleine Änderung bauen, neues Paket hochladen.
- [ ] Aufgabe „Hochgeladenes Paket prüfen“ (meldet fehlende Dateien), dann „Update ausführen“ (Passwort), Wartungsmodus geht von selbst an und wieder aus.
- [ ] Stichprobe danach: Anmelden, Seminarliste, Rundschreiben-Vorschau.

## K. Vor dem echten Start
- [ ] Alle Notizen aus diesem Probelauf abgearbeitet.
- [ ] Rechtliche Punkte erledigt: Vertragstext, Datenschutzerklärung (Platzhalter gefüllt), Impressum, Aufbewahrungsfristen.
- [ ] Mailvorlagen durchgelesen und angepasst.
- [ ] Testdaten und Testkonten gelöscht oder eigene Datenbank für den Echtbetrieb neu angelegt.
- [ ] Zwei Personen im Verein wissen, wo Sicherung, `.env` und Zugangsdaten liegen.
- [ ] Dependabot-Hinweise auf GitHub geprüft (`composer audit`, `npm audit`).

## Wenn etwas nicht klappt
Erste Anlaufstelle ist `storage/logs/` (neueste Datei per FTP) und Abschnitt 9 „Fehlersuche“ in `68_WEBHOSTING.md`. Schicken Sie mir die Fehlermeldung mit Datum und Uhrzeit, dann kann ich sie dem Code zuordnen.
