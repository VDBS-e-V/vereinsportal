# Server-Baseline und Bootstrap-Runbook

Dieses Dokument bereitet die erste Staging-VM vor. Es setzt keine noch offene Betriebsentscheidung aus Issue #19 automatisch auf `entschieden` und aktiviert keinen Deployment-Workflow.

Die verbindliche Architekturentscheidung bleibt in `docs/27_OPERATIONS_DECISION_MATRIX.md` und Issue #19. Dieses Runbook beantwortet stattdessen die Frage: **Was muss auf der später gewählten VM nachweisbar vorhanden und korrekt konfiguriert sein, bevor ein erstes Staging-Deployment sinnvoll ist?**

## 1. Anwendungsseitig belegte Mindestbasis

Aktueller Repository-Stand:

- Laravel 13
- PHP `^8.4.1`
- CI prüft PHP 8.4.1 und 8.5
- Composer 2
- vollständige Quality-Integration gegen MySQL 8.4
- Frontend-Build mit Node.js 22 und Vite
- mindestens benötigte PHP-Extensions aus CI: `mbstring`, `pdo_mysql`, `fileinfo`, `sodium`
- `QUEUE_CONNECTION=database`
- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- privates Standard-Dateisystem: `local`
- Queue Worker erforderlich
- Laravel Scheduler erforderlich
- echter Mailversand in Staging/Produktion erforderlich
- privater persistenter Storage für Mitgliedschaftsdokumente erforderlich

Die Produktions-/Staging-VM muss zusätzlich `composer check-platform-reqs` für den tatsächlich deployten Lockfile-Stand erfüllen. Die vier CI-Extensions sind damit eine belegte Mindestmenge, aber keine künstliche Obergrenze.

## 2. Noch vor Bootstrap festzulegen

Diese Werte kommen aus Issue #19 und werden vor dem ersten realen Serveraufbau ausdrücklich entschieden:

- Betriebssystem und unterstützte Release-Linie
- Webserver
- PHP 8.4.x oder 8.5 als produktive Linie
- MySQL 8.4 oder bewusst getestete Alternative
- Queue-Prozessmanager (`systemd` oder Supervisor)
- Scheduler über Cron oder `systemd`-Timer
- Mail-Provider
- Deployment-Transport
- Backup-Ziel
- Monitoring-/Alarmierungsweg

Empfohlenes Startbild aus `docs/27_OPERATIONS_DECISION_MATRIX.md`: eine einzelne Linux-VM, Nginx + PHP-FPM, MySQL 8.4, database queue/cache/session und persistenter Shared-Storage.

## 3. Benutzer- und Rechte-Modell

Keine dauerhafte Anwendungsausführung als `root`.

Mindestens zwei Verantwortungsbereiche unterscheiden:

### Systemadministration

Darf:

- Pakete installieren/aktualisieren
- Webserver/PHP/MySQL konfigurieren
- Systemdienste verwalten
- Firewall und SSH konfigurieren

### Deployment / Runtime

Soll nur die für das Portal notwendigen Rechte erhalten.

Zielbild:

- eigener Deploy-Benutzer mit SSH-Key
- eigener Runtime-Benutzer bzw. Webserver/PHP-FPM-Kontext
- Deploy-Benutzer darf Release-Verzeichnisse anlegen und `current` umschalten
- Runtime darf nur notwendige Laravel-Runtime-Pfade schreiben
- `.env` und private Fachdateien sind nicht für beliebige Systemnutzer lesbar
- kein dauerhaftes interaktives Root-Login für Deployments

Wenn Deploy- und Runtime-Benutzer getrennt sind, gemeinsame Gruppe und kontrollierte Group-Rechte statt globaler Schreibrechte verwenden.

## 4. Ziel-Verzeichnisstruktur

Empfohlen:

```text
/srv/vereinsportal/
  current -> releases/<release-id>
  releases/
    <release-id>/
  shared/
    .env
    storage/
```

Eigenschaften:

- Release-Verzeichnisse sind nach Aktivierung unveränderlich
- `current` ist der einzige Webserver-/Runtime-Einstiegspunkt
- `.env` liegt außerhalb der Releases
- `shared/storage` überlebt Release-Wechsel
- Mitgliedschaftsdokumente liegen dadurch nicht in einem flüchtigen Release
- ältere Releases können für Code-Rollback begrenzt aufbewahrt werden

Vor Freigabe nachweisen:

- [ ] `current` zeigt auf genau einen existierenden Release
- [ ] `shared/.env` ist nicht im Webroot erreichbar
- [ ] `shared/storage` ist persistent
- [ ] Laravel sieht im aktiven Release denselben Storage
- [ ] Webserver liefert private Storage-Dateien nicht direkt aus

## 5. Netzwerk und Ports

Von außen normalerweise erforderlich:

- TCP 443 für HTTPS
- optional TCP 80 ausschließlich für Redirect/ACME
- SSH nur für definierte Administrations-/Deployment-Zugänge

Nicht öffentlich exponieren, sofern kein bewusstes anderes Modell beschlossen wurde:

- MySQL
- PHP-FPM
- interne Monitoring-/Adminports

Prüfen:

- [ ] Firewall-Regeln dokumentiert
- [ ] MySQL bindet nicht unnötig öffentlich
- [ ] PHP-FPM ist nur lokal bzw. über Unix-Socket erreichbar
- [ ] SSH-Passwortlogin deaktiviert, sofern der gewählte Betriebsweg Schlüsselzugang erlaubt
- [ ] TLS-Zertifikat erneuert sich automatisiert
- [ ] HTTP wird auf HTTPS umgeleitet

## 6. PHP und Composer

Vor erstem Deployment:

- [ ] gewählte PHP-Version liegt innerhalb der unterstützten Linie
- [ ] CLI und PHP-FPM verwenden dieselbe beabsichtigte Haupt-/Minor-Version
- [ ] `mbstring` vorhanden
- [ ] `pdo_mysql` vorhanden
- [ ] `fileinfo` vorhanden
- [ ] `sodium` vorhanden
- [ ] Composer 2 verfügbar oder Composer-Abhängigkeiten kommen vollständig aus einem vertrauenswürdig erzeugten Artefakt
- [ ] `composer check-platform-reqs` erfolgreich

Zusätzlich prüfen:

- `upload_max_filesize`
- `post_max_size`
- `memory_limit`
- `max_execution_time`
- Zeitzone

Dafür noch keine beliebigen Werte erfinden. Die Werte werden aus realen Uploadgrößen und Laufzeitmessungen abgeleitet und in #19 dokumentiert.

## 7. Datenbank

Aktuelle Referenz: MySQL 8.4.

Vor Staging:

- [ ] eigene Datenbank für Staging
- [ ] eigener DB-Benutzer für die Anwendung
- [ ] keine Nutzung des MySQL-Root-Kontos durch Laravel
- [ ] DB-Zugang nur aus dem erforderlichen Netz/Host
- [ ] Zeichensatz/Collation bewusst festgelegt und Migrationen erfolgreich getestet
- [ ] `php artisan migrate --force` auf leerer Staging-Datenbank erfolgreich
- [ ] Backup-Zugang getrennt vom normalen App-Zugang, falls das Backup-Verfahren zusätzliche Rechte benötigt

Die Anwendung darf nicht von manuellen Schemaänderungen außerhalb der Laravel-Migrationen abhängen.

## 8. Environment und Secrets

`shared/.env` ist serverseitige Konfiguration, kein Release-Artefakt.

Mindestens bewusst setzen:

```text
APP_ENV=staging|production
APP_KEY=<stabiler geheimer Schlüssel>
APP_DEBUG=false
APP_URL=https://...
VDB_MY_DOMAIN=...

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME=...
MAIL_SUPPORT_ADDRESS=...
```

Für HTTPS zusätzlich verifizieren:

- sichere Session-Cookies
- korrekte Session-Domain
- korrekte URL-/Domain-Werte für signierte Links

Nicht übernehmen:

- `VDB_DEV_ADMIN_*` aus lokaler Entwicklung
- `APP_DEBUG=true`
- lokale Mail-Log-Konfiguration
- Testdatenbank-Zugangsdaten

Der `APP_KEY` muss releaseübergreifend stabil bleiben. Ein spontaner Schlüsselwechsel kann verschlüsselte Anwendungsdaten bzw. laufende sicherheitsrelevante Abläufe unbrauchbar machen.

## 9. Laravel-Runtime-Pfade

Schreibbar für die Runtime:

- `storage/`
- `bootstrap/cache`

Bei Shared-Storage insbesondere prüfen:

- `storage/app`
- `storage/framework`
- `storage/logs`

Private Fachdateien müssen im persistenten Bereich landen. Temporäre Framework-Daten dürfen beim Release-Wechsel nicht zu inkonsistenten Rechten führen.

Abnahme:

- [ ] Anwendung kann Logs schreiben
- [ ] Sessions funktionieren
- [ ] Cache-Locks funktionieren
- [ ] Queue-Jobs können geschrieben/verarbeitet werden
- [ ] Mitgliedschaftsdokument kann in Staging hochgeladen und nach Release-Wechsel weiterhin autorisiert heruntergeladen werden

## 10. Webserver und PHP-FPM

Unabhängig von Nginx/Apache:

- Document Root zeigt auf `<current>/public`
- keine Freigabe des Repository-Roots
- keine direkte Freigabe von `.env`
- keine direkte Freigabe von `storage/app/private` bzw. privatem Laravel-Storage
- PHP-Dateien außerhalb des vorgesehenen Frontcontrollers nicht versehentlich als Download ausliefern
- HTTPS erzwingen
- geeignete Security-Header separat prüfen

Nach jedem Release muss der Webserver weiterhin auf `current/public` zeigen, nicht auf einen konkreten Release-Pfad.

## 11. Queue Worker

Die Anwendung verwendet echte queued Jobs; ein dauerhaft laufender Worker ist damit Betriebsanforderung.

Vor Staging entscheiden und dokumentieren:

- Prozessmanager
- Befehl
- Queue-Namen
- `--tries`
- Timeout
- Backoff
- Restart-Verfahren
- Start nach Server-Reboot
- Umgang mit `failed_jobs`

Mindestabnahme:

- [ ] Worker startet nach VM-Neustart automatisch
- [ ] Worker verarbeitet einen realen Staging-Mailjob
- [ ] absichtlich beendeter Worker wird als Fehler erkannt bzw. neu gestartet
- [ ] Deployment führt einen kontrollierten Worker-Restart durch
- [ ] fehlgeschlagene Jobs sind sichtbar und lösen den vorgesehenen Betriebsprozess aus

## 12. Scheduler

Im Repository ist mindestens ein stündlicher Cleanup-Job geplant. Laravel muss dafür mindestens minütlich durch `schedule:run` angestoßen werden.

Variante Cron:

```text
* * * * * cd /srv/vereinsportal/current && php artisan schedule:run >> /dev/null 2>&1
```

oder ein äquivalenter `systemd`-Timer.

Abnahme:

- [ ] Scheduler läuft minütlich
- [ ] arbeitet gegen `current`
- [ ] überlebt Server-Reboot
- [ ] Ausfall ist über Heartbeat/Monitoring erkennbar
- [ ] Scheduler und Queue Worker werden als getrennte Betriebsabhängigkeiten überwacht

## 13. Mail

Staging benötigt einen echten Testpfad, nicht `MAIL_MAILER=log`.

Vor Staging:

- [ ] Provider ausgewählt
- [ ] SMTP/API-Credentials im Secret Store bzw. geschützter Serverkonfiguration
- [ ] Absenderdomain vorbereitet
- [ ] SPF/DKIM geprüft
- [ ] DMARC bewusst festgelegt
- [ ] Support-Adresse konfiguriert
- [ ] mindestens ein realer Zustelltest
- [ ] Queue- und Delivery-Status im Portal nachvollziehbar

## 14. Logging und Rotation

Vor Produktivbetrieb mindestens klären:

- Speicherort Laravel-Logs
- Webserver-Access-/Error-Logs
- PHP-FPM-Logs
- MySQL-Logs soweit benötigt
- `journalctl`/Dienstlogs für Worker und Scheduler
- Rotation
- Aufbewahrungsdauer
- Alarmierung bei wiederholten 5xx-/Worker-/Backup-Fehlern

Keine personenbezogenen Inhalte unnötig in Infrastruktur-Logs aufnehmen.

## 15. OS- und Dienst-Härtung

Vor Staging produktionsnah vorbereiten:

- [ ] Sicherheitsupdates eingerichtet
- [ ] nur notwendige Pakete/Dienste installiert
- [ ] Firewall aktiv
- [ ] Schlüsselbasierter SSH-Zugang
- [ ] unnötige Standardkonten/-zugänge deaktiviert
- [ ] Datenbank nicht öffentlich erreichbar
- [ ] Zeitsynchronisation aktiv
- [ ] ausreichend freier Speicherplatz überwacht
- [ ] Backup-Agent bzw. Backup-Job eingerichtet

## 16. Bootstrap-Abnahmeprotokoll

Für jede neue Staging-/Production-VM dokumentieren:

```text
Umgebung:
Hostname:
OS / Version:
Webserver / Version:
PHP CLI / FPM:
Composer:
Datenbank / Version:
Node vorhanden auf Server: ja/nein
Deploy-Benutzer:
Runtime-Benutzer:
Release-Root:
Shared-Storage:
TLS:
Queue-Prozessmanager:
Scheduler:
Mail-Provider:
Backup-Ziel:
Monitoring:
Bootstrap-Datum:
Geprüft durch:
Offene Abweichungen:
```

Keine Secrets oder privaten Schlüssel in dieses Protokoll schreiben.

## 17. Gate zum ersten Deployment

Ein erstes Staging-Deployment startet erst, wenn:

1. die funktionale Beta abgenommen ist,
2. #13/#14/#15 abgeschlossen sind,
3. die für Staging nötigen Entscheidungen in #19 ausdrücklich getroffen sind,
4. die Server-Baseline dieses Dokuments erfüllt ist,
5. Backup/Restore nach `docs/29_BACKUP_RESTORE_RUNBOOK.md` vorbereitet ist,
6. die Staging-Abnahme nach `docs/30_STAGING_PREFLIGHT_AND_SMOKE_TEST.md` ausführbar ist.
