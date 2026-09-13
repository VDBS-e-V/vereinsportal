# Betriebs- und Deployment-Entscheidungsmatrix

Dieses Dokument bereitet Issue #19 vor. Es unterscheidet zwischen bereits belegten Anforderungen, empfohlenen Startwerten und tatsächlich noch zu treffenden Betriebsentscheidungen.

Es aktiviert weder Staging noch Produktion und ersetzt keine spätere Abnahme. Empfehlungen werden erst zu Beschlüssen, wenn sie in #19 ausdrücklich als entschieden dokumentiert sind.

## 1. Bereits feststehende Rahmenbedingungen

- Hosting-Anbieter: STRATO
- Servermodell: eigene VM/VPS
- Anwendung: Laravel 13
- PHP-Basis: 8.4.1 oder neuer innerhalb der getesteten Linie
- CI prüft PHP 8.4.1 und 8.5
- vollständiger Quality-Job läuft gegen MySQL 8.4
- Frontend-Build läuft mit Node.js 22 und Vite
- Queue Worker ist erforderlich
- Laravel Scheduler ist erforderlich
- Session, Cache und Queue sind aktuell datenbankbasiert
- privater persistenter Storage ist für Mitgliedschaftsdokumente erforderlich
- echter Mailversand ist für Staging/Produktion erforderlich
- Backup/Restore muss SQL-Daten und private Fachdateien gemeinsam berücksichtigen

## 2. Entscheidungsmatrix

| Thema | Empfehlung für die erste Betriebsstufe | Alternativen | Status |
| --- | --- | --- | --- |
| Betriebssystem | schlanke, aktiv gepflegte Linux-Serverdistribution mit langfristigem Security-Support | andere unterstützte Linux-Distribution | offen |
| Webserver | Nginx + PHP-FPM | Apache + PHP-FPM | offen |
| PHP | 8.5, sofern auf dem gewählten OS sauber paketiert und alle Plattformanforderungen erfüllt sind; sonst 8.4.x | andere von Composer erlaubte Version | offen |
| Datenbank | MySQL 8.4 | kompatible alternative SQL-Plattform nach eigener Integrationsprüfung | offen |
| Queue | Laravel Database Queue mit dauerhaftem Worker | Redis später bei begründetem Bedarf | offen |
| Worker-Prozess | eigener `systemd`-Service | Supervisor | offen |
| Scheduler | `php artisan schedule:run` minütlich per systemd timer oder Cron | Plattform-Scheduler | offen |
| Frontend-Build | reproduzierbarer Build in CI, Release enthält fertige Artefakte | Build auf Zielserver | offen |
| Deployment | versionierte Releases + atomarer `current`-Symlink | In-place-Deployment | offen |
| private Dateien | gemeinsames persistentes `shared/storage` außerhalb einzelner Releases | Object Storage | offen |
| `.env` / Secrets | außerhalb des Release-Verzeichnisses, nur für Runtime-/Deploy-User lesbar | externer Secret Store | offen |
| Mail | transaktionaler SMTP/API-Provider mit SPF/DKIM/DMARC | eigener SMTP-Relay | offen |
| TLS | automatisiert erneuerbares Zertifikat und HTTPS-only | providerverwaltetes Zertifikat | offen |
| Monitoring | Uptime + App-Fehler + Worker/Scheduler-Heartbeat | umfassender APM-Dienst | offen |
| Backup | verschlüsseltes, automatisiertes Off-Host-Backup für DB + Fachdateien | VM-Snapshot nur ergänzend | offen |

## 3. Empfohlene erste Serverarchitektur

Für eine einzelne Vereinsportal-Instanz ohne nachgewiesenen Skalierungsdruck ist eine einfache, nachvollziehbare Ein-VM-Architektur sinnvoller als vorschnelle Verteilung auf viele Dienste.

Vorgeschlagenes Startbild:

```text
Internet
  |
HTTPS
  |
Nginx
  |
PHP-FPM / Laravel
  |
+-- MySQL 8.4
+-- database queue
+-- database cache/session
+-- privater persistenter Storage

systemd:
- php-fpm
- queue worker
- optional eigener Scheduler-Timer
```

Der Datenbankdienst kann anfangs auf derselben VM laufen, wenn Ressourcen, Backups und Restore sauber beherrscht werden. Ein externer Managed-DB-Dienst ist technisch ebenfalls möglich, aber für die erste Stufe nicht zwingend.

## 4. Release-Verzeichnisstruktur

Empfohlenes atomisches Modell:

```text
/srv/vereinsportal/
  current -> releases/<release-id>
  releases/
    <release-id>/
  shared/
    .env
    storage/
```

Ziele:

- jeder Release ist unveränderlich
- `current` zeigt auf genau einen freigegebenen Release
- `.env` und persistente Fachdateien liegen außerhalb des Release-Verzeichnisses
- Rollback des Codes bedeutet im Normalfall nur Umschalten des Symlinks
- alte Releases können begrenzt aufbewahrt werden

Wichtig: Datenbankmigrationen sind dadurch nicht automatisch rollbackbar. Die Migrationsstrategie muss separat sicherstellen, dass Code-Rollback und Datenbankschema kompatibel bleiben.

## 5. Deployment-Abfolge als Zielbild

Noch kein Workflow-Code, aber die spätere Reihenfolge sollte ungefähr so aussehen:

1. grünen `main`-Commit bzw. Release festlegen
2. Frontend-Artefakte reproduzierbar bauen
3. Release auf Zielserver übertragen
4. Composer-Abhängigkeiten für Produktion installieren oder als geprüftes Artefakt mitliefern
5. Shared `.env` und `storage` einbinden
6. Konfiguration validieren
7. vor riskanten Migrationen Backup/Restore-Punkt sicherstellen
8. Wartungsmodus nur falls tatsächlich nötig aktivieren
9. Migrationen mit `--force` ausführen
10. Laravel-Caches gezielt neu aufbauen
11. `current` atomar auf den neuen Release umschalten
12. Queue Worker kontrolliert neu starten
13. Scheduler-/Prozesszustand prüfen
14. Health Check ausführen
15. Smoke Tests für Login, Konto, Verwaltung/Vorstand, Mail und Dokumentzugriff ausführen
16. Wartungsmodus beenden
17. Deployment-Ergebnis und Release-SHA protokollieren

Bei Fehlern darf der Workflow nicht blind weiterlaufen.

## 6. Queue Worker

Aktuelle Baseline: database queue.

Für den ersten Betrieb reicht ein einzelner dauerhaft laufender Worker, solange Lastmessungen nichts anderes zeigen.

Zu entscheiden:

- Anzahl Worker
- Queue-Namen/Prioritäten
- Timeout
- Anzahl Versuche
- Backoff
- Verhalten bei `failed_jobs`
- Neustart nach Deployment
- Alarmierung bei dauerhaft fehlgeschlagenen Jobs

Empfehlung: zunächst konservativ starten und erst nach Messdaten parallelisieren.

## 7. Scheduler

Der Scheduler muss zuverlässig mindestens minütlich angestoßen werden, damit Laravel die eigentlichen Zeitpläne selbst entscheidet.

Zwei einfache Varianten:

### Cron

```text
* * * * * cd /srv/vereinsportal/current && php artisan schedule:run >> /dev/null 2>&1
```

### systemd timer

Geeignet, wenn alle Betriebsprozesse einheitlich über systemd überwacht werden sollen.

Unabhängig von der Variante muss ein fehlender Scheduler-Aufruf erkennbar werden, z. B. über einen Heartbeat.

## 8. Persistente Mitgliedschaftsdokumente

Mitgliedschaftsdokumente sind private Fachdateien und dürfen nicht im öffentlichen Webroot liegen.

Für ein lokales Storage-Modell bedeutet das:

- `storage/app/...` liegt im persistenten Shared-Bereich
- jeder Release bindet denselben privaten Storage ein
- Webserver liefert diesen Pfad nicht direkt aus
- Download erfolgt weiterhin durch die autorisierte Anwendung
- Backup und Restore müssen die Dateien einschließen
- Dateirechte erlauben Schreiben durch die PHP-Runtime, aber keine unnötige Lesbarkeit durch andere Systemnutzer

Vor Produktionsfreigabe muss ein Restore-Test nachweisen, dass Datenbankeinträge und Dateien wieder konsistent zusammenpassen.

## 9. Backup- und Restore-Modell

VM-Snapshots sind hilfreich, aber allein kein ausreichendes fachliches Backup-Konzept.

Mindestens getrennt planen:

### Datenbank

- automatischer Dump oder konsistentes Datenbankbackup
- definierte Frequenz
- Aufbewahrungsregeln
- verschlüsselte Off-Host-Kopie
- zusätzlicher Sicherungspunkt vor riskanten Migrationen

### Fachdateien

- privater Mitgliedschaftsdokument-Storage
- inkrementelles oder versioniertes Backup
- verschlüsselte Off-Host-Kopie
- gleiche oder nachvollziehbar passende Aufbewahrung zur Datenbank

### Restore

Ein dokumentierter Restore muss beantworten:

1. welche DB-Version wird wiederhergestellt?
2. welcher Dateistand gehört dazu?
3. wie wird verhindert, dass DB und Dateien zeitlich auseinanderlaufen?
4. wie wird der Restore vor Freigabe geprüft?
5. wie lange dauert ein realistischer Restore?

Die erste Staging-Phase muss einen echten Restore-Test enthalten.

## 10. Server- und Deployment-Sicherheit

Für die spätere Umsetzung als Baseline einplanen:

- eigener Deploy-/Runtime-Benutzer statt dauerhafter Root-Nutzung
- SSH nur mit Schlüsseln
- unnötige Dienste/Ports schließen
- Datenbank nicht unnötig öffentlich exponieren
- HTTPS erzwingen
- sichere Session-Cookies in HTTPS-Umgebung
- `.env` nicht im Repository und nicht öffentlich lesbar
- minimale Dateirechte
- regelmäßige OS-/PHP-/Datenbank-Sicherheitsupdates
- Deploy-Credentials mit minimal erforderlichen Rechten
- keine Production-Secrets in Pull-Request-Workflows

## 11. Mail-Betrieb

Vor Staging entscheiden:

- Provider
- SMTP oder API
- Absenderdomain
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `MAIL_SUPPORT_ADDRESS`
- SPF
- DKIM
- DMARC
- Bounce-/Fehlerbehandlung

Ein erfolgreicher Queue-Job bedeutet nicht automatisch erfolgreiche Zustellung. Staging muss auch den Delivery-Status und mindestens einen realen Zustellpfad testen.

## 12. Health Checks und Smoke Tests

Ein Health Check sollte zunächst klein bleiben und keine vertraulichen Details veröffentlichen.

Zu prüfen sind mindestens:

- Anwendung bootet
- Datenbank erreichbar
- notwendige Runtime-Verzeichnisse nutzbar

Betriebsmonitoring soll zusätzlich erkennen:

- HTTP-Ausfall
- wiederholte 5xx-Fehler
- fehlgeschlagene Queue-Jobs
- ausgefallenen Queue Worker
- ausgefallenen Scheduler
- knapp werdenden Speicherplatz
- fehlgeschlagene Backups

Nach jedem Deployment als Smoke-Test mindestens:

- Loginseite
- Login
- Konto-/Portalbereich
- Verwaltung oder Vorstand mit passender Rolle
- eine schreibende Testaktion nur in Staging
- Mail/Queue
- privater Dokumentzugriff

## 13. Entscheidungen in #19 dokumentieren

Für jede offene Zeile der Matrix reicht dieses Format:

```text
Entscheidung:
Gewählte Variante:
Begründung:
Betriebliche Konsequenz:
Offene Risiken:
Datum:
```

Erst wenn Betriebssystem, Webserver, PHP, Datenbank, Worker, Scheduler, Mail, Storage, Deployment, Backup/Restore und Monitoring ausreichend entschieden sind, soll `deploy-staging.yml` entworfen werden.
