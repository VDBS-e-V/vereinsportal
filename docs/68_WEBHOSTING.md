# 68 Betrieb auf Webhosting (Strato) ohne Konsole

Stand: Paket A des Projektplans (`docs/projektplan/A_BETRIEB_WEBHOSTING.md`), erster Teil umgesetzt: Prüfskript, Paket bauen, Einrichtung im Browser, Cron-Aufruf, Zustandsprüfungen. **Nicht auf dem echten Hosting erprobt**, die Schritte unten sind daher beim ersten Durchlauf zu prüfen.

## 1 Ergebnis der Hosting-Prüfung (Strato)
Aus `scripts/hostcheck.php`: PHP 8.4.27, alle Pflicht- und gewünschten Erweiterungen vorhanden (`zip`, `gd`, `intl`, `bcmath`, `exif`, `sodium`), `memory_limit` 512M, `max_execution_time` 240 s, Upload 128M, `exec`/`proc_open` verfügbar (das Portal braucht sie nicht), HTTPS aktiv, Datenbank **MariaDB 11.8.8** (Server-Zeichensatz `utf8mb3`, das Portal verbindet sich mit `utf8mb4`).

## 2 Einmalige Vorbereitung im Strato-Kundenmenü
1. **PHP-Version** der Domain auf 8.4 (oder 8.5) stellen.
2. **Datenbank** anlegen (Name, Benutzer, Passwort, Host notieren).
3. **Domain oder Subdomain** für das Portal anlegen. **Document-Root** auf den Ordner `<Ordner>/public` stellen (z. B. `portal/public`). Alles andere liegt eine Ebene darüber und ist nicht aus dem Netz erreichbar.
4. **HTTPS-Zertifikat** aktivieren, Weiterleitung auf HTTPS einschalten.
5. **SMTP-Zugang** (Postfach) für den Versand einrichten, Zugangsdaten notieren.
6. **Cronjob-Dienst** (cron-job.org): Konto anlegen, später Aufruf einrichten (Abschnitt 6).

## 3 Paket bauen (auf Ihrem Rechner)
PowerShell im Projektordner:
```
.\scripts\build-release.ps1
```
Das Skript baut das Frontend, kopiert die Dateien ohne `.env`, `tests`, `docs`, `node_modules`, installiert `vendor/` ohne Entwicklungspakete, schreibt `release-manifest.json` (Prüfsummen) und erzeugt `build\portal-<Version>.zip`. Das ZIP entpacken Sie lokal; hochgeladen wird der **Inhalt** des Ordners `build\release`.

## 4 Erstes Hochladen
1. Per FTP (FileZilla o. Ä.) den Inhalt von `build\release` in den Portal-Ordner laden (z. B. `portal/`). **Reihenfolge:** zuerst `vendor/` (viele Dateien, mit mehreren parallelen Verbindungen), dann `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `public/`, zuletzt die Dateien im Hauptordner (`artisan`, `composer.json`, `RELEASE`, `release-manifest.json` …).
2. `deploy/env.strato.example` als `.env` speichern, Werte eintragen (`APP_URL`, Datenbank, SMTP, `SETUP_TOKEN` und `CRON_KEY` als zufällige Zeichenfolgen) und in den Hauptordner (neben `artisan`) hochladen. `APP_KEY` bleibt zuerst leer.
3. Schreibrechte für `storage/` und `bootstrap/cache/` (und alle Unterordner) auf 755 oder 775 stellen (im FTP-Programm: Dateiattribute).

## 5 Einrichtung im Browser
1. `https://IHRE-DOMAIN/einrichtung` öffnen, Token (`SETUP_TOKEN`) eingeben.
2. **Voraussetzungen** prüfen lassen. Probleme beheben, „Erneut prüfen“.
3. **Schlüssel:** Fehlt `APP_KEY`, zeigt die Seite eine Zeile zum Eintragen in die `.env`. Datei speichern, hochladen, „Erneut prüfen“.
4. **Datenbank anlegen** (Tabellen und Rollen).
5. **Erste Administration** anlegen (Passwort mindestens 12 Zeichen mit Groß- und Kleinbuchstaben und Ziffer).
6. **Abschluss:** erzeugt die Schlüssel für Anbindungen (SSO) und schreibt die Sperrdatei `storage/app/setup.lock`. Danach antwortet `/einrichtung` mit 404.
7. **`SETUP_TOKEN` aus der `.env` entfernen** und die Datei erneut hochladen.
8. Anmelden, Zwei-Faktor einrichten (Pflicht für die Administration).

## 6 Cron mit cron-job.org
Der Planer (Erinnerungen, geplante Rundschreiben, Aufräumen) und die Warteschlange (Rundschreiben-Versand) laufen über **einen** Aufruf:
- Adresse: `https://IHRE-DOMAIN/cron/<CRON_KEY>`
- Methode GET, Zeitplan **jede Minute**, Zeitüberschreitung 60 Sekunden, Benachrichtigung bei mehrfachem Fehlschlag aktivieren.
- Antwort bei Erfolg: leer (204). Falscher oder fehlender Schlüssel: 404.
- Je Aufruf arbeitet das Portal höchstens `PORTAL_TICK_SECONDS` (Standard 45) Sekunden lang Aufträge ab. Läuft ein Aufruf noch, startet der nächste nicht parallel.
- Kontrolle: Admins › System › Zustand zeigt „Cron-Aufruf“ und „Planer“ mit dem Alter des letzten Laufs.
- Wird der Schlüssel erneuert: `.env` ändern, Adresse beim Dienst anpassen.

## 7 Aktualisieren
1. Datenbankexport im Strato-Kundenmenü (phpMyAdmin) anlegen und Datum notieren.
2. Im Portal **Wartungsmodus einschalten** (Admins › System › Aufgaben).
3. Neues Paket bauen, per FTP hochladen: erst `vendor/` (nur geänderte Dateien), `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `public/`, zuletzt `RELEASE` und `release-manifest.json`. **Nie** die `.env` oder `storage/` überschreiben.
4. Aufgabe **„Hochgeladenes Paket prüfen“**: meldet fehlende oder abweichende Dateien.
5. Aufgabe **„Migrationen ausführen“** (Passwortabfrage), danach **„Alle Zwischenspeicher leeren“**.
6. **Wartungsmodus beenden**, Stichprobe (Anmelden, Seminarliste, Rundschreiben-Vorschau).

## 8 Sicherung
- **Datenbank:** Export im Kundenmenü vor jedem Update, zusätzlich regelmäßig (z. B. wöchentlich).
- **Dateien:** `.env`, `storage/oauth-private.key`, `storage/oauth-public.key` und der Ordner `storage/app/private` (Uploads) per FTP herunterladen. Ohne `APP_KEY` sind verschlüsselte Daten (IBAN, Steuernummer, Zwei-Faktor) nicht lesbar.

## 9 Fehlersuche
| Beobachtung | Ursache und Abhilfe |
|---|---|
| Weiße Seite oder 500 | `storage/logs/` per FTP öffnen, neueste Datei lesen. Häufig: Schreibrechte von `storage/` oder `bootstrap/cache/`, fehlender `APP_KEY`, falsche Datenbankdaten. |
| Einrichtung zeigt 500 und im Log „No application encryption key“ | Der `APP_KEY` fehlt noch. Seit Version vom 10.11.2026 läuft `/einrichtung` auch ohne Schlüssel und ohne Datenbanktabellen (Sitzungen sind ohne Schlüssel unverschlüsselt, die Begrenzung nutzt den Datei-Zwischenspeicher). Schlüssel auf der Einrichtungsseite ablesen und in die `.env` eintragen. |
| „No application encryption key“ | `APP_KEY` in `.env` eintragen, dann Admins › System › „Alle Zwischenspeicher leeren“ (oder `bootstrap/cache/config.php` per FTP löschen). |
| Änderungen an `.env` wirken nicht | Konfigurations-Cache: Aufgabe „Konfiguration leeren“ oder `bootstrap/cache/config.php` löschen. |
| Cron läuft nicht | Zustand zeigt „noch nie aufgerufen“: Adresse und Schlüssel beim Dienst prüfen, HTTPS-Weiterleitung beachten (kein Redirect auf eine andere Adresse). |
| Mails kommen nicht an | Testmail (Admins › System › Einstellungen), SMTP-Daten, Absenderadresse und SPF prüfen (Rundschreiben › Zustellqualität › Absenderprüfung). |
| Seite läuft, sieht aber aus wie reiner Text ohne Gestaltung (Bilder da, keine Farben, Menü als Liste) | `public/build/` fehlt auf dem Server (CSS und JavaScript). Früher schloss das Paketskript es versehentlich aus. Prüfen: `https://DOMAIN/build/manifest.json` muss eine JSON-Datei liefern. Abhilfe: Ordner `public/build` vom Rechner (nach `npm run build`) per FTP nach `public/build` hochladen. |
| Seite zeigt die Standardseite des Hosters | Document-Root zeigt nicht auf `public/`. |
| `/.env` oder `/storage` im Browser erreichbar | **Sofort** Document-Root korrigieren: er muss auf `public/` zeigen, nicht auf den Hauptordner. |

## Hinweis: Befehle im Browser
Alle Befehle des Portals (`feedback:prune`, `contracts:remind`, `sso:ensure-keys` …) sind auch bei Aufrufen über den Browser registriert (Systemsteuerung › Aufgaben, Update ausführen, Einrichtung). Die Anbindungsschlüssel erzeugt das Portal selbst mit OpenSSL; die Passport-Befehle gibt es im Browser nicht. Fehlen die Schlüssel nach der Einrichtung, erzeugt sie Admins › Anbindungen (oder „Update ausführen“).

## 10 Was noch fehlt in Paket A
Wochenende-Probelauf auf dem echten Hosting, Test der PDF-Erzeugung (kommt mit Paket D), Anleitung in einfacher Sprache, Checkliste siehe `docs/76_STRATO_PROBELAUF_CHECKLISTE.md`; Anpassung von `docs/05`, `27`, `28`, `30`, `31`.


## Update einspielen (`portal:update`)

Nach dem Hochladen einer neuen Version genügt **ein** Schritt:

- mit Konsole (lokal/Test): `php artisan portal:update`
- ohne Konsole: Systemsteuerung → Betrieb → **Update ausführen** (Passwort erforderlich)

Ablauf: Paket gegen `release-manifest.json` prüfen → Datenbankverbindung prüfen → Wartungsmodus an → `migrate --force` → Zwischenspeicher leeren → Wartungsmodus aus.
Ist das Paket unvollständig oder die Datenbank nicht erreichbar, wird **nichts** verändert. Scheitert die Migration, bleibt das Portal bewusst in der Wartung; die Ausgabe nennt den geheimen Zugangslink. Die Datenbanksicherung vorher ersetzt der Befehl nicht.

## Neue Einstellungen in die `.env` übernehmen (`env:sync`)
Kommen mit einer neuen Version Einstellungen in die `.env.example` dazu, ergänzt `php artisan env:sync` sie in Ihrer `.env`. Vorhandene Werte werden **nie** geändert, nichts wird gelöscht; vorher entsteht eine Sicherungskopie `.env.backup-<Datum>`. Mit `--dry-run` sehen Sie nur, was ergänzt würde. Danach `php artisan config:clear`. Auf dem Webhosting ohne Konsole vergleichen Sie `deploy/env.strato.example` von Hand mit Ihrer `.env`.
