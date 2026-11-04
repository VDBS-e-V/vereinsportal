# Paket A: Betrieb auf Webhosting ohne Konsole

Aufwand: **9 ET**. Ziel: Das Portal läuft per FTP-Upload, wird im Browser eingerichtet und aktualisiert, und alle Hintergrundarbeit (Planer, Warteschlange) läuft über **einen Cron-Eintrag im Kundenmenü des Hosters**.

## 1 Ausgangslage im Projekt

| Baustein | Stand |
|---|---|
| Systemsteuerung `/admins/system` | Vorhanden: Zustandsprüfungen, Aufgaben (Migrationen, Zwischenspeicher, geplante Aufgaben), Wartungsmodus mit Geheimlink, Protokolldatei, fehlgeschlagene Aufträge, Einstellungen mit Testmail. Nur für die Rolle Administration (`system.manage`). |
| Planer | `routes/console.php` plant u. a. `mailings:dispatch-due` (jede Minute), tägliche Aufräumbefehle; Herzschlag-Prüfung für „Planer läuft“. |
| Warteschlange | `QUEUE_CONNECTION=database`; Rundschreiben nutzen `SendMailing` in Paketen. |
| Erste Administration | nur über Konsole `role:grant-administration`. |
| Passport-Schlüssel | nur über Konsole `passport:keys`. |
| Betriebsdoku | `docs/05`, `27`, `28` beschreiben VM, Nginx, systemd. |

## 2 Entscheidungen

1. **Cron-Aufruf:** Der Hoster ruft jede Minute (oder so oft wie erlaubt, mindestens alle 5 Minuten) `php /pfad/artisan portal:tick` auf. Falls das Kundenmenü nur URLs erlaubt, ruft es `https://…/cron/<token>` auf. Beide Wege führen denselben Code aus.
2. **Ein Befehl für alles:** `portal:tick` führt `schedule:run` aus und arbeitet danach höchstens 45 Sekunden Aufträge ab (`queue:work --stop-when-empty --max-time=45`). Eine Sperre (`Cache::lock`) verhindert, dass zwei Läufe gleichzeitig laufen.
3. **Einrichtung im Browser** statt Konsole, mit Einmal-Token aus der `.env` und Sperrdatei danach.
4. **Release-Paket** wird lokal gebaut; der Server bekommt nur fertige Dateien.
5. **Document-Root:** Variante 1 (frei wählbar, zeigt auf `public/`) bevorzugt, Variante 2 (fest `public_html`) vorbereitet.

## 3 Arbeitspakete

### A1 Hoster-Prüfung und Dokument `docs/68_WEBHOSTING.md` (0,5 ET)
Checkliste, die Sie beim Hoster abfragen oder im Kundenmenü ablesen:

| Frage | Mindestens | Wo nachsehen |
|---|---|---|
| PHP-Version wählbar? | 8.4.1 oder höher | Kundenmenü, „PHP-Version“ |
| PHP-Erweiterungen | `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `ctype`, `json`, `tokenizer`, `xml`, `zip`, `gd`, `intl` | Kundenmenü oder `phpinfo` (Testdatei `info.php`, danach löschen) |
| Cron | Befehl mit PHP-Aufruf oder URL-Aufruf, Takt | Kundenmenü, „Cronjobs“ |
| Document-Root | frei wählbar? | Kundenmenü, „Domains“ |
| Speicher | mind. 2 GB (Uploads, Mappen) | Vertrag |
| MySQL | 8.0 oder MariaDB 10.6+ | Kundenmenü |
| `max_execution_time` / `memory_limit` | 30 s / 256 MB | `phpinfo` |
| Mail | SMTP-Zugang, Stundenlimit, SPF-Eintrag des Hosters | Kundenmenü, Handbuch |
| TLS | Zertifikat, erzwungenes HTTPS | Kundenmenü |
| Backups | tägliche Datenbanksicherung? | Vertrag |

Ergebnis: ausgefüllte Tabelle in `docs/68_WEBHOSTING.md`. Daraus folgt, ob A3-Variante 1 oder 2 gilt.

### A2 Release-Paket (1,5 ET)
**Dateien:** `scripts/build-release.ps1` (Windows/XAMPP, Ihr Weg), `scripts/build-release.sh`, `scripts/release-manifest.php`.

Ablauf des Skripts:
1. `git status` sauber? Sonst Abbruch mit Hinweis.
2. `composer install --no-dev --optimize-autoloader --no-interaction` in einem **Kopieordner** (`build/release`), nicht im Arbeitsordner.
3. `npm ci && npm run build` im Arbeitsordner; `public/build` in den Kopieordner.
4. Auslassen: `.git`, `.github`, `tests`, `docs`, `node_modules`, `storage/logs/*`, `storage/framework/cache/*`, `.env`, `database/*.sqlite`, `build`.
5. `RELEASE`-Datei schreiben (Datum, Commit, Version) und `release-manifest.json` mit **SHA-256 jeder Datei**.
6. ZIP `portal-<Version>.zip` plus `UPLOAD.txt` (Reihenfolge, siehe A5).

Die Systemsteuerung zeigt `RELEASE` an. Eine neue Seite „Paket prüfen“ liest `release-manifest.json` und vergleicht die Prüfsummen mit den Dateien auf dem Server. Das erkennt **unvollständige FTP-Uploads**.

Tests: `ReleaseManifestTest` (Manifest-Klasse erkennt fehlende, veränderte, zusätzliche Dateien).

### A3 Zustandsprüfung für Webhosting (0,5 ET)
Erweiterung von `SystemChecks`: `zip`, `gd`, `intl`, `fileinfo`, Schreibrechte `storage/` und `bootstrap/cache`, PHP-Version, `max_execution_time`, `memory_limit`, `upload_max_filesize` gegen die Grenzen aus den Paketen D/G, `.env` nicht erreichbar (HTTP-Selbsttest `GET /.env` darf nicht 200 liefern), HTTPS aktiv, `APP_DEBUG=false` im Livebetrieb, `APP_URL` passt zum Aufruf, Cron-Herzschlag (letzter `portal:tick`-Lauf).

### A4 Verzeichnislayout und Zugriffsschutz (1 ET)
**Variante 1 (Document-Root frei):** Document-Root auf `<Pfad>/public`. Fertig.

**Variante 2 (Document-Root fest `public_html`):**
```
/home/kunde/
  portal/              ← Anwendung (app, bootstrap, config, vendor, storage, .env …)
  public_html/         ← Inhalt von public/ (index.php, build/, .htaccess, images/ …)
```
- `public_html/index.php` bekommt angepasste Pfade (`__DIR__.'/../portal/…'`). Das Paket liefert `deploy/public_html/index.php` fertig mit.
- `.htaccess` im Paket: Rewrite auf `index.php`, Zugriff auf `.env`, `.git`, `*.log`, `composer.*`, `artisan` sperren, Sicherheitsköpfe, HTTPS erzwingen, Caching für `build/`.
- **Wichtig:** `storage/` und `.env` liegen **außerhalb** von `public_html`. Test über die Systemprüfung A3.
- Privater Speicher: `storage/app/private` (kein Symlink nötig, weil Dateien über Controller laufen). Prüfen, dass kein Controller `Storage::url()` auf dem öffentlichen Datenträger benötigt (Artikelbilder laufen bereits über eine Route).

### A5 Erstinstallation im Browser (2 ET)
**Neues Modul `Setup`** (`app/Modules/Setup`), eigenständig ohne Anmeldung, nur aktiv, wenn `storage/app/setup.lock` fehlt **und** `SETUP_TOKEN` in der `.env` gesetzt ist.

Ablauf (Seiten `/einrichtung/…`, jede Seite prüft Token in der Sitzung):
1. **Token** eingeben (aus der `.env`, die Sie per FTP hochladen).
2. **Voraussetzungen:** alle Prüfungen aus A3, rot/grün mit Erklärung.
3. **Datenbank:** Verbindung testen (Werte kommen aus der `.env`, die Seite schreibt sie nicht), Hinweis bei fehlenden Rechten.
4. **Schlüssel:** `APP_KEY` fehlt? Die Seite erzeugt einen Schlüssel und zeigt ihn **einmal** zum Eintragen in die `.env` (Schreiben der `.env` per Webserver wird bewusst vermieden). Danach „Weiter“.
5. **Datenbank anlegen:** `Artisan::call('migrate', ['--force' => true])` und Seeder für Rollen (`RoleSeeder`), Ergebnis anzeigen (gekürzte Ausgabe, Geheimnisse geschwärzt mit `Redactor`).
6. **Erste Administration:** Name, E-Mail, Passwort (Stärkeregeln), Geburtsdatum falls Pflicht; Rolle `administration` und `board_member` optional; Zwei-Faktor-Einrichtung wird beim ersten Login erzwungen (vorhanden).
7. **Anbindungsschlüssel:** `Artisan::call('passport:keys')`, Schlüssel in `storage/` (Hinweis: mit sichern).
8. **Abschluss:** Mail-Testversand (optional), Sperrdatei `setup.lock` schreiben, Hinweis „`SETUP_TOKEN` aus der `.env` entfernen“.

Sicherheit: Sperrdatei verhindert Wiederholung; Token Vergleich mit `hash_equals`; Drosselung 10/Minute; alle Eingaben validiert; Seite sendet `X-Robots-Tag: noindex`; nach Abschluss liefert `/einrichtung` 404.

Tests (`SetupTest`): ohne Token 404; falscher Token 403; Token richtig und Sperrdatei vorhanden 404; legt Administration mit Rollen an; zweiter Aufruf gesperrt; schreibt die Sperrdatei; kein Passwort im Protokoll.

### A6 Planer und Warteschlange über Cron (1,5 ET)
**Befehl `portal:tick`** (`app/Console/Commands` oder Modul System):
1. Sperre holen (`Cache::lock('portal-tick', 55)`), sonst sofort beenden.
2. Herzschlag schreiben (vorhanden: `system:heartbeat`-Logik).
3. `schedule:run` (Planer entscheidet, was dran ist).
4. `queue:work --stop-when-empty --max-time=45 --tries=3` (Standardwarteschlange).
5. Protokoll: Zahl der verarbeiteten Aufträge in `system_task_runs` (kein Inhalt).

**Web-Cron** `GET /cron/{token}`: Token aus `CRON_TOKEN`, `hash_equals`, Drosselung, nur wenn gesetzt; ruft `portal:tick` auf; antwortet `204`. Dient als Notlösung, falls das Kundenmenü nur URLs aufrufen kann.

**Taktung:** Empfohlen jede Minute. Bei 5-Minuten-Takt gilt: `mailings:dispatch-due` startet verspätet; geplante Schreiben kommen bis zu 5 Minuten später.

**Mailversand mit Hosterlimit:** Neue Einstellung im Rundschreiben-Bereich `MAILINGS_PER_HOUR` (zusätzlich zu `per_minute`). Der Versandauftrag zählt die in der letzten Stunde gesendeten Mails (Tabelle `mailing_deliveries`, Status `sent`, `sent_at`) und pausiert bei Erreichen. Anzeige auf der Fortschrittsseite: „Hoster-Grenze erreicht, weiter um …“.

Tests: `TickTest` (Sperre, Herzschlag, arbeitet Auftrag ab, bricht nach `max-time` ab), `CronTokenTest`, `HourlyLimitTest`.

### A7 Aktualisieren ohne Konsole (1 ET)
Ablauf `docs/68_WEBHOSTING.md`, Abschnitt „Update“:
1. Systemsteuerung → **Sicherung prüfen** (Datenbank-Dump über das Hostermenü; Datum notieren).
2. **Wartungsmodus an** (vorhanden).
3. FTP-Upload in dieser Reihenfolge: `vendor/` (nur Änderungen), `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `public/` bzw. `public_html/`, zuletzt `RELEASE` und `release-manifest.json`.
4. Systemsteuerung → **Paket prüfen** (A2) → grün.
5. **Migrationen ausführen** (vorhanden, mit Passwortabfrage); Konfiguration/Ansichten/Routen leeren (vorhanden).
6. **Wartungsmodus aus**; Stichprobe durch den Testablauf (Anmelden, Seminarliste, Rundschreiben-Vorschau).

Ergänzungen im Code:
- Banner in der Systemsteuerung „Datenbank hinkt hinterher“ (vorhanden als Migrationsprüfung) mit direktem Knopf.
- Während Migrationen läuft Wartungsmodus automatisch (Task `migrationen` schaltet `down` vor, `up` nach Erfolg).
- Dateien im Paket, die auf dem Server nie überschrieben werden dürfen, stehen in `UPLOAD.txt` (`.env`, `storage/`).

### A8 Sicherung und Wiederherstellung ohne Konsole (0,5 ET)
`docs/68_WEBHOSTING.md`: Datenbank-Dump im Kundenmenü (phpMyAdmin Export) vor jedem Update; Dateisicherung per FTP-Download von `storage/app/private` (Uploads) und `storage/` Schlüssel; Rückspielen per Import und Upload. Optional: Admin-Seite **„Sicherung herunterladen“** erzeugt einen DB-Export per PHP (nur kleine Datenbanken, `mysqldump` ist auf Webhosting meist nicht verfügbar; Fallback Tabelle für Tabelle als SQL, begrenzt auf Administration, Passwortabfrage). Entscheidung nach A1.

### A9 Betriebsdoku ersetzen (0,5 ET)
`docs/05`, `27`, `28`, `30`, `31`: Hosting als Hauptweg, VM als Alternative. `docs/99_CURRENT_HANDOFF.md` aktualisieren.

## 4 Datenmodell
Keine fachlichen Tabellen. Neu: Spalte `system_task_runs.items` (Anzahl verarbeitete Aufträge, optional).

## 5 Abnahme (Meilenstein M1)
1. Paket lokal gebaut, hochgeladen, **Prüfsummen grün**.
2. Einrichtung im Browser bis zum Login der Administration, Zwei-Faktor aktiv.
3. Cron läuft: Systemprüfung zeigt „Planer läuft“ unter 2 Minuten alt.
4. Testrundschreiben an fünf Adressen kommt an, Abmeldelink funktioniert.
5. Update-Probe: kleine Änderung hochladen, Migrationen per Knopf, kein Fehler.
6. Wiederherstellungsprobe auf einer Testdatenbank.
7. `GET /.env`, `GET /storage/...`, `GET /composer.json` liefern 403/404.

## 6 Risiken
| Risiko | Gegenmaßnahme |
|---|---|
| Hoster erlaubt kein `exec`/`proc_open` für `queue:work` | `portal:tick` ruft die Warteschlange im selben Prozess auf (`Artisan::call`), kein `exec`. |
| FTP-Upload bricht ab | Wartungsmodus, Reihenfolge, Prüfsummen. |
| `max_execution_time` 30 s | `--max-time=25` einstellbar (`PORTAL_TICK_SECONDS`). |
| Cron nur im 15-Minuten-Takt | Geplante Schreiben verspätet, Hinweis in Oberfläche; kritische Versände sofort statt geplant. |
| Passport-Schlüssel gehen verloren | Teil der Sicherung (A8), Hinweis in der Einrichtung. |
| `.env` wird versehentlich überschrieben | `UPLOAD.txt`, Paket enthält keine `.env`. |

## 7 Noch offen (ich brauche Ihre Antwort in A1)
Document-Root frei oder fest; ob URL-Cron oder PHP-Cron; Stundenlimit für Mails; genauer Hostername für die Doku.
