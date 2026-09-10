# Phase 5 – Deployment Planung

## Status

Die Deployment-Automatisierung ist bewusst noch nicht begonnen. Zuerst wird in Issue #19 die Hosting- und Betriebsarchitektur festgelegt.

Das Repository definiert aber bereits konkrete Laufzeitanforderungen. Diese Anforderungen sind unabhängig vom später gewählten Hosting-Anbieter und werden hier als Grundlage für die Entscheidung festgehalten.

## Grundsatz

Deployment wird nicht automatisch an jeden Push auf `main` gekoppelt. Zuerst müssen Hosting, Backup, Restore, Rollback, Environment-Schutz und die benötigten Betriebsprozesse verbindlich geklärt sein.

Kein Deployment-Workflow soll während seiner Implementierung grundlegende Betriebsentscheidungen erfinden.

## Bereits durch die Anwendung belegte Laufzeitanforderungen

### PHP und Datenbank

- minimale unterstützte PHP-Basis: 8.4.1
- PHP 8.5 wird ebenfalls kontinuierlich in CI geprüft
- die Zielumgebung muss `composer check-platform-reqs` erfüllen
- die Anwendung benötigt einen dauerhaft verfügbaren SQL-Dienst
- das konkrete Produktions-Datenbanksystem und dessen Version werden in Issue #19 festgelegt

### Queue Worker

Die Anwendung besitzt echte asynchrone Jobs. Insbesondere läuft der zentrale Template-Mailversand über einen verschlüsselten `ShouldQueue`-Job.

Daraus folgt:

- ein Queue Worker ist im realen Betrieb erforderlich
- der Worker muss zuverlässig gestartet und überwacht werden
- Releases müssen einen kontrollierten Worker-/Prozess-Restart ermöglichen
- `APP_KEY` muss dauerhaft stabil und sicher gespeichert werden

Aktuelle Baseline:

- `QUEUE_CONNECTION=database`
- die Tabellen `jobs`, `job_batches` und `failed_jobs` sind bereits migriert

Redis ist damit für die erste Betriebsstufe nicht zwingend erforderlich. Ein späterer Wechsel bleibt möglich, wenn Last oder Skalierung ihn rechtfertigen.

### Scheduler

`routes/console.php` plant die Bereinigung abgelaufener Registrierungsanfragen stündlich. Der Cleanup selbst ist ein queued und unique Job.

Daraus folgt:

- der Laravel Scheduler muss regelmäßig angestoßen werden, zum Beispiel über Cron oder eine gleichwertige Plattformfunktion
- Scheduler und Queue Worker sind getrennte Betriebsanforderungen
- ein Ausfall von Scheduler oder Worker muss im Betrieb erkennbar sein

### Sessions und Cache

Aktuelle Baseline:

- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- entsprechende Tabellen für Sessions, Cache und Cache-Locks sind vorhanden

Damit kann eine erste Installation Sessions, Cache und Queue über denselben sauber betriebenen SQL-Dienst abbilden. Redis ist keine Voraussetzung für den ersten Staging-Aufbau.

### Mail

Registrierung, Passwortprozesse, E-Mail-Änderung, Zwei-Faktor-Abläufe und Kontolöschungsprozesse verwenden das zentrale Mail-/Delivery-System.

Vor Staging festlegen:

- echter Mail-Provider bzw. SMTP/API-Zugang
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `MAIL_SUPPORT_ADDRESS`

`MAIL_MAILER=log` ist nur für lokale Entwicklung geeignet und reicht nicht für die Staging-Abnahme.

### Domain, DNS und HTTPS

Der Benutzerbereich ist an `VDB_MY_DOMAIN` gebunden. Signierte Links werden in sicherheitsrelevanten Identity-Flows verwendet.

Vor einem vollständigen Staging-Test müssen deshalb feststehen:

- Staging-Domain
- spätere Production-Domain
- DNS-Zuordnung
- HTTPS/TLS
- passendes `APP_URL`
- passendes `VDB_MY_DOMAIN`

Für HTTPS ist außerdem `SESSION_SECURE_COOKIE=true` als Produktionskonfiguration zu prüfen und im Staging zu verifizieren.

### Dateisystem

Laravel ist aktuell für lokalen privaten und öffentlichen Storage sowie optional S3 konfiguriert.

Für den aktuellen Anwendungscode ist noch kein produktiver Fach-Upload-Workflow belegt. Dennoch benötigt die Runtime:

- Schreibrechte für `storage/`
- Schreibrechte für `bootstrap/cache`

Ob Fachdateien später releaseübergreifend lokal persistent oder in Object Storage liegen, wird entschieden, sobald ein echter Datei-/Upload-Anwendungsfall dies verlangt. S3 wird nicht vorsorglich zur Voraussetzung gemacht.

### Entwicklungsadmin

Der `DevelopmentAdminSeeder` läuft nur in `local` und `testing` und beendet sich in Produktion ohne Änderungen.

Für Produktion gilt trotzdem:

- `APP_ENV=production`
- keine `VDB_DEV_ADMIN_*`-Werte in Production-Secrets übernehmen
- echte administrative Zugänge separat und bewusst provisionieren

## Minimale Hosting-Fähigkeiten

Ein geeigneter erster Zielbetrieb muss mindestens ermöglichen:

1. PHP innerhalb der unterstützten 8.4.1+-Basis und alle Composer-Plattformanforderungen
2. dauerhaft verfügbaren SQL-Dienst
3. Web-Runtime mit eigener Domain und HTTPS
4. zuverlässig laufenden Queue Worker
5. Cron/Scheduler oder gleichwertige Plattformfunktion
6. ausgehenden Mailversand über einen echten Provider
7. sicheren Secret Store mit stabilem `APP_KEY`
8. schreibbare Laravel-Runtime-Verzeichnisse
9. Datenbank-Backup und Restore
10. kontrollierbaren Worker-/Prozess-Restart beim Deployment
11. Zugriff auf Logs und eine geeignete Monitoring-/Alarmierungsmöglichkeit

Hosting-Angebote, die keine Background Worker oder keinen Scheduler unterstützen, passen nur, wenn die Anwendungsarchitektur vorher bewusst geändert wird.

## Staging

Zielmodell nach Abschluss der Betriebsentscheidungen:

- geschütztes GitHub Environment `staging`
- eigene Staging-Domain und eigene Staging-Secrets
- zunächst manuelles Deployment über `workflow_dispatch`
- Deployment ausschließlich aus einem bekannten grünen `main`-Commit
- keine Production-Secrets in Pull-Request-Workflows
- nach Deployment Health Check und definierte Smoke Tests
- Rollback und Restore praktisch testen

Ein optionales Preview-Deployment für ausgewählte Pull Requests ist erst später zu bewerten.

## Produktion

Zielmodell:

`main` → Tag `vX.Y.Z` → GitHub Release → Production-Deployment

Der Produktionsworkflow wird erst nach erfolgreichem Staging- und Release-Probelauf implementiert bzw. aktiviert.

## GitHub Environments

Geplant:

- `staging`
- `production`

Für `production`:

- Required Reviewer, sobald organisatorisch möglich
- Prevent self-review, sobald mehrere Reviewer verfügbar sind
- Branch-/Tag-Einschränkungen passend zum Release-Modell
- Secrets ausschließlich im Environment bzw. einem gleichwertig geschützten Secret Store

## Secrets und Variablen

Die tatsächlich benötigten Secret-Namen werden aus dem gewählten Deployment-Modell abgeleitet.

Frühere Platzhalter wie:

- `PROD_HOST`
- `PROD_USER`
- `PROD_SSH_KEY`
- `PROD_PATH`
- `PROD_URL`

sind keine verbindliche Schnittstelle. Sie werden nur verwendet, wenn das gewählte Hosting-/Deployment-Modell sie tatsächlich benötigt.

Unabhängig vom Transport werden mindestens Anwendungs- und Provider-Secrets benötigt, zum Beispiel:

- stabiler `APP_KEY`
- Datenbankzugang
- Mail-Provider-Zugang
- produktive Domain-/URL-Konfiguration

## Geplante Workflows

Nach Abschluss von Issue #19 zunächst:

`.github/workflows/deploy-staging.yml`

Danach, nach erfolgreichem Staging- und Release-Probelauf:

`.github/workflows/deploy-production.yml`

Produktions-Trigger:

- GitHub Release `published`
- optional bewusst dokumentierter manueller Notfall-Trigger

## Vor jeder Automatisierung klären

Tracking: Issue #19

- Hosting / Servertyp / Plattform
- Betriebssystem und Webserver bzw. Managed Runtime
- produktive PHP-Version
- Datenbanksystem und Version
- Queue-Worker-Betrieb
- Scheduler-Ausführung
- Mail-Provider
- Storage-/Persistenzmodell
- Deployment-Transport
- Schlüssel-/Credential-Management
- Build-Artefakt oder Build auf Server
- Migrationsstrategie
- Wartungsmodus
- Health Check und Smoke Tests
- Logging und Monitoring
- Backup und Aufbewahrung
- Restore-Verfahren
- Code- und Datenbank-Rollback
- Verantwortlichkeiten

## Gate für die Implementierung von Staging

`deploy-staging.yml` wird erst entworfen, wenn Issue #19 ausreichend beantwortet ist, sodass Hosting-, Secret-, Migrations-, Backup-, Restore- und Rollback-Verhalten nicht während der Workflow-Implementierung improvisiert werden müssen.
