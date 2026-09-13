# Phase 5 – Deployment Planung

## Status

Die Deployment-Automatisierung ist bewusst noch nicht begonnen. Zuerst wird in Issue #19 die Hosting- und Betriebsarchitektur festgelegt. Der funktionale Beta-/QA-Gate aus #22/#16 bleibt Voraussetzung für die Aktivierung dieser Phase.

Bereits entschieden:

- Hosting-Anbieter: STRATO
- Servermodell: eigene VM/VPS

Das Repository definiert bereits konkrete Laufzeit- und Persistenzanforderungen. Diese Anforderungen sind unabhängig von den noch offenen Betriebsentscheidungen und werden hier als Grundlage festgehalten.

## Grundsatz

Deployment wird nicht automatisch an jeden Push auf `main` gekoppelt. Zuerst müssen Hosting, Backup, Restore, Rollback, Environment-Schutz und die benötigten Betriebsprozesse verbindlich geklärt sein.

Kein Deployment-Workflow soll während seiner Implementierung grundlegende Betriebsentscheidungen erfinden.

## Bereits durch die Anwendung belegte Laufzeitanforderungen

### PHP und Datenbank

- minimale unterstützte PHP-Basis: 8.4.1
- PHP 8.5 wird ebenfalls kontinuierlich in CI geprüft
- die Zielumgebung muss `composer check-platform-reqs` erfüllen
- die CI installiert mindestens `mbstring`, `pdo_mysql`, `fileinfo` und `sodium`
- die Anwendung benötigt einen dauerhaft verfügbaren SQL-Dienst
- der vollständige Quality-Job läuft aktuell gegen MySQL 8.4 und führt dort Migrationen sowie die gesamte Pest-Suite aus

Damit ist MySQL 8.4 derzeit die am stärksten automatisiert verifizierte Datenbankreferenz. Die endgültige Produktionsentscheidung bleibt trotzdem Teil von Issue #19.

### Frontend-Build

Die Frontend-Artefakte werden mit Vite erzeugt. Die CI verwendet aktuell Node.js 22 und führt `npm ci` sowie `npm run build` aus.

Daraus folgt:

- Node.js ist für den Build erforderlich, aber nicht zwingend für die laufende PHP-Web-Runtime
- ob Node.js auf dem Zielserver installiert wird, hängt von der späteren Entscheidung `CI-Artefakt` vs. `Build auf Zielserver` ab
- ein vorgebautes Release muss die erzeugten Frontend-Artefakte vollständig enthalten

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

Registrierung, Passwortprozesse, E-Mail-Änderung, Zwei-Faktor-Abläufe, Portal-Einladungen und Kontolöschungsprozesse verwenden das zentrale Mail-/Delivery-System.

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

### Dateisystem und persistente Fachdateien

Die Anwendung besitzt inzwischen einen produktiven Fach-Upload-Workflow: Mitgliedschaftsdokumente werden auf dem privaten Laravel-Disk `local` gespeichert und kontrolliert über den Anwendungscontroller ausgeliefert. Sie liegen bewusst nicht auf dem öffentlichen Disk.

Daraus folgt für Staging und Produktion:

- Schreibrechte für `storage/`
- Schreibrechte für `bootstrap/cache`
- der private lokale Storage mit Mitgliedschaftsdokumenten muss releaseübergreifend persistent sein
- Deployments dürfen diese Dateien weder überschreiben noch beim Release-Wechsel verlieren
- Mitgliedschaftsdokumente müssen in Backup, Restore und Aufbewahrungsregeln einbezogen werden
- direkte öffentliche Webserver-Freigabe dieses privaten Dokumentverzeichnisses ist nicht vorgesehen

Ob die erste Betriebsstufe diesen privaten Storage lokal auf der VM hält oder später auf Object Storage migriert, bleibt eine Architekturentscheidung aus #19. S3 wird nicht vorsorglich zur Voraussetzung gemacht.

### Entwicklungsadmin

Der `DevelopmentAdminSeeder` läuft nur in `local` und `testing` und beendet sich in Produktion ohne Änderungen.

Für Produktion gilt trotzdem:

- `APP_ENV=production`
- keine `VDB_DEV_ADMIN_*`-Werte in Production-Secrets übernehmen
- echte administrative Zugänge separat und bewusst provisionieren

## Minimale Hosting-Fähigkeiten

Ein geeigneter erster Zielbetrieb muss mindestens ermöglichen:

1. PHP innerhalb der unterstützten 8.4.1+-Basis und alle Composer-Plattformanforderungen
2. dauerhaft verfügbaren SQL-Dienst; MySQL 8.4 ist die aktuelle CI-Referenz
3. Web-Runtime mit eigener Domain und HTTPS
4. zuverlässig laufenden Queue Worker
5. Cron/Scheduler oder gleichwertige Plattformfunktion
6. ausgehenden Mailversand über einen echten Provider
7. sicheren Secret Store mit stabilem `APP_KEY`
8. schreibbare Laravel-Runtime-Verzeichnisse
9. persistenten privaten Storage für Mitgliedschaftsdokumente
10. Datenbank- und Datei-Backup samt dokumentiertem Restore
11. kontrollierbaren Worker-/Prozess-Restart beim Deployment
12. Zugriff auf Logs und eine geeignete Monitoring-/Alarmierungsmöglichkeit
13. einen definierten Frontend-Build-Pfad; aktuelle Build-Referenz ist Node.js 22

Hosting-Angebote, die keine Background Worker, keinen Scheduler oder keinen persistenten privaten Storage unterstützen, passen nur, wenn die Anwendungsarchitektur vorher bewusst geändert wird.

## Staging

Zielmodell nach Abschluss der Betriebsentscheidungen:

- geschütztes GitHub Environment `staging`
- eigene Staging-Domain und eigene Staging-Secrets
- zunächst manuelles Deployment über `workflow_dispatch`
- Deployment ausschließlich aus einem bekannten grünen `main`-Commit
- keine Production-Secrets in Pull-Request-Workflows
- nach Deployment Health Check und definierte Smoke Tests
- Rollback und Restore praktisch testen
- beim Restore sowohl SQL-Daten als auch persistente Mitgliedschaftsdokumente berücksichtigen

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

Bereits entschieden:

- [x] Hosting-Anbieter: STRATO
- [x] Servertyp: eigene VM/VPS

Noch festzulegen:

- [ ] Betriebssystem
- [ ] Webserver
- [ ] produktive PHP-Version innerhalb der unterstützten Basis
- [ ] Datenbanksystem und Version; MySQL 8.4 ist Referenzkandidat
- [ ] Queue-Worker-Betrieb
- [ ] Scheduler-Ausführung
- [ ] Mail-Provider
- [ ] Storage-/Persistenzmodell für private Mitgliedschaftsdokumente
- [ ] Deployment-Transport
- [ ] Schlüssel-/Credential-Management
- [ ] Build-Artefakt oder Build auf Server
- [ ] Migrationsstrategie
- [ ] Wartungsmodus
- [ ] Health Check und Smoke Tests
- [ ] Logging und Monitoring
- [ ] Backup und Aufbewahrung für SQL und Fachdateien
- [ ] Restore-Verfahren
- [ ] Code- und Datenbank-Rollback
- [ ] Verantwortlichkeiten

## Gate für die Implementierung von Staging

`deploy-staging.yml` wird erst entworfen, wenn die funktionale Beta abgenommen und Issue #19 ausreichend beantwortet ist, sodass Hosting-, Secret-, Migrations-, Persistenz-, Backup-, Restore- und Rollback-Verhalten nicht während der Workflow-Implementierung improvisiert werden müssen.
