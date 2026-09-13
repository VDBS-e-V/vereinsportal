# Staging-Preflight und Smoke-Test

Dieses Dokument beschreibt die erste reproduzierbare Staging-Abnahme nach Abschluss des funktionalen Beta-Gates. Es ist absichtlich noch kein Deployment-Workflow und setzt keine offenen Entscheidungen aus Issue #19 automatisch auf beschlossen.

Ziel ist, dass `deploy-staging.yml` später nur noch einen bereits fachlich und betrieblich definierten Ablauf automatisiert, statt während der Implementierung Architekturentscheidungen zu erfinden.

## 1. Start-Gate

Staging wird erst aktiv aufgebaut, wenn alle folgenden Punkte erfüllt sind:

- [ ] funktionale Beta aus Issue #22 dokumentiert bestanden
- [ ] Design-/Accessibility-QA aus Issue #16 abgeschlossen
- [ ] Repository-Gate #13 abgeschlossen
- [ ] Actions-Settings #14 abgeschlossen
- [ ] Security-Settings #15 abgeschlossen
- [ ] die für Staging erforderlichen Entscheidungen aus #19 ausdrücklich getroffen
- [ ] Server-Baseline nach `docs/28_SERVER_BASELINE_AND_BOOTSTRAP.md` erfüllt
- [ ] Backup-/Restore-Verfahren nach `docs/29_BACKUP_RESTORE_RUNBOOK.md` vorbereitet

Ein grüner `main`-Commit allein reicht nicht für den Start dieses Gates.

## 2. Staging soll produktionsnah, aber ungefährlich sein

Staging soll dieselbe grundsätzliche Betriebsarchitektur wie Produktion verwenden:

- gleiche PHP-Linie
- gleicher Webserver-Typ
- gleiche Datenbankfamilie/-version
- gleicher Queue-/Scheduler-Mechanismus
- gleiche Release-Verzeichnisstruktur
- gleicher private-Storage-Mechanismus
- gleicher Deployment-Mechanismus

Abweichungen sind möglich, müssen aber ausdrücklich dokumentiert werden.

Staging darf dagegen bewusst andere Daten, Domains und Providerzugänge verwenden.

## 3. Harte Trennung zu Produktion

Staging benötigt eigene:

- Domain
- Datenbank
- Datenbank-Credentials
- `.env`
- Mail-Credentials bzw. Mail-Sandbox
- privaten Storage
- Backup-Sets
- Monitoring-Bezeichnung
- GitHub-Environment-Secrets

Niemals Production-Datenbank oder Production-Storage als bequeme Testquelle anbinden.

Keine echten Mitgliederdaten für Staging verwenden, sofern nicht ein gesondert legitimierter und datenschutzkonformer Prozess beschlossen wurde.

## 4. GitHub Environment `staging`

Vor erstem automatisierten Deployment anlegen und dokumentieren:

- [ ] Environment `staging` vorhanden
- [ ] Environment-Secrets nur dort bzw. in gleichwertigem Secret Store
- [ ] Branch-Einschränkung auf den vorgesehenen Deployment-Pfad
- [ ] keine Secrets in PR-Workflows
- [ ] Reviewer-Regel bewusst bewertet
- [ ] Self-review-Regel bewusst bewertet
- [ ] Deployment-Historie in GitHub nachvollziehbar

Solange nur eine Person zuverlässig administriert, darf ein Reviewer-Zwang nicht so konfiguriert werden, dass Staging organisatorisch unbenutzbar wird. Die Entscheidung ist zu dokumentieren.

## 5. Staging-Domain und TLS

Vor Deployment:

- [ ] DNS zeigt auf die Staging-VM
- [ ] TLS-Zertifikat gültig
- [ ] automatische Erneuerung eingerichtet
- [ ] HTTP → HTTPS
- [ ] `APP_URL` exakt passend
- [ ] `VDB_MY_DOMAIN` exakt passend
- [ ] Session-Domain passend
- [ ] sichere Cookies unter HTTPS geprüft
- [ ] signierte Links funktionieren unter der echten Staging-Domain

Domain-/URL-Fehler sind besonders kritisch, weil Identity-Flows signierte Links verwenden.

## 6. Staging-Konfiguration

Mindestens kontrollieren:

```text
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://<staging-domain>
VDB_MY_DOMAIN=<staging-domain-or-required-subdomain>

DB_CONNECTION=mysql
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
```

Zusätzlich:

- stabiler eigener Staging-`APP_KEY`
- echter bzw. sicherer Staging-Mailpfad
- keine `VDB_DEV_ADMIN_*`-Werte
- keine Production-Secrets
- sinnvolle Logging-Stufe

Vor Freigabe vollständige Konfiguration mit `php artisan about` bzw. anderen sicheren Runtime-Checks prüfen, ohne Secrets in Logs auszugeben.

## 7. Release-Kandidat festlegen

Jeder Staging-Lauf bezieht sich auf einen konkreten Commit-SHA.

Protokollieren:

```text
Commit-SHA:
Branch: main
CI-Run:
Security-Run:
Build-Artefakt-ID bzw. Build-Verfahren:
Deployment-Zeitpunkt:
```

Voraussetzung:

- Required Checks grün
- CI-Kompatibilitätsjobs grün
- Security grün
- kein anderer Commit wird stillschweigend während des Deployments eingesetzt

## 8. Build-/Artefakt-Preflight

Wenn die in #19 empfohlene Variante `Build in CI` beschlossen wird:

- [ ] `npm ci`
- [ ] `npm run build`
- [ ] Composer-Produktion-Abhängigkeiten reproduzierbar erzeugt
- [ ] Artifact eindeutig an Commit-SHA gebunden
- [ ] Artifact enthält keine `.env` oder Secrets
- [ ] Checksums bzw. unveränderbare Artifact-ID vorhanden

Wenn stattdessen auf dem Server gebaut wird, müssen Node/Composer-Versionen und Buildschritte dort explizit Teil der Server-Baseline werden.

## 9. Deployment-Preflight auf dem Server

Vor dem ersten Release:

- [ ] `/srv/vereinsportal/releases` vorhanden
- [ ] `/srv/vereinsportal/shared` vorhanden
- [ ] `shared/.env` vorhanden und geschützt
- [ ] `shared/storage` vorhanden und schreibbar
- [ ] ausreichender freier Speicher
- [ ] Datenbank erreichbar
- [ ] Mailprovider erreichbar
- [ ] Queue-/Scheduler-Dienste installiert
- [ ] Webserver zeigt auf `/srv/vereinsportal/current/public`
- [ ] Backup-Ziel erreichbar
- [ ] Monitoring aktiv

## 10. Empfohlene Deployment-Abfolge

Die spätere Automatisierung sollte diese Reihenfolge abbilden:

1. Release-Kandidat und Commit-SHA prüfen.
2. neues Release-Verzeichnis anlegen.
3. geprüftes Artifact übertragen/entpacken.
4. Produktionsabhängigkeiten vollständig bereitstellen.
5. Shared `.env` verlinken.
6. Shared `storage` verlinken.
7. Runtime-Dateirechte prüfen.
8. `composer check-platform-reqs` ausführen.
9. Laravel-Konfiguration validieren.
10. vor riskanten Migrationen vorgesehenes Backup-Set erzeugen/verifizieren.
11. falls erforderlich Wartungsmodus aktivieren.
12. `php artisan migrate --force` ausführen.
13. notwendige Laravel-Caches kontrolliert neu aufbauen.
14. `current` atomar auf den neuen Release umschalten.
15. Queue Worker kontrolliert neu starten.
16. Scheduler-Zustand prüfen.
17. Health Check ausführen.
18. Smoke Tests aus diesem Dokument durchführen.
19. Wartungsmodus beenden, sofern noch aktiv.
20. Ergebnis und aktiven Commit-SHA protokollieren.

Bei Fehlern nicht blind zum nächsten Schritt übergehen.

## 11. Technischer Health Check

Ein öffentlicher Health-Endpunkt darf keine vertraulichen Details offenlegen.

Mindestens intern bzw. im Deployment prüfen:

- Laravel bootet
- Datenbank erreichbar
- Runtime-Verzeichnisse schreibbar
- aktiver Release-SHA identifizierbar

Betriebsmonitoring zusätzlich:

- HTTP-Uptime
- 5xx-Häufung
- Worker läuft
- Scheduler-Heartbeat
- fehlgeschlagene Jobs
- Backup-Erfolg
- Speicherplatz

## 12. Basis-Smoke-Test nach jedem Deployment

### Öffentlich / Login

- [ ] öffentliche Start-/Loginseite lädt über HTTPS
- [ ] Assets laden ohne 404/5xx
- [ ] CSRF/Formulare funktionieren
- [ ] Login mit Staging-Testkonto funktioniert
- [ ] Logout funktioniert

### Konto / 2FA

- [ ] Konto-/Portalbereich erreichbar
- [ ] Passwortfunktion stichprobenartig
- [ ] 2FA-Flow mit Testkonto
- [ ] Session bleibt erwartungsgemäß erhalten

### Verwaltung

Mit `administration`:

- [ ] `/verwaltung` erreichbar
- [ ] Personenliste lädt
- [ ] Testperson öffnen
- [ ] zulässige Teständerung speichern
- [ ] `/vorstand` bleibt ohne Vorstandrolle gesperrt

### Vorstand

Mit `board_member`:

- [ ] `/vorstand` erreichbar
- [ ] Mitgliedschaft öffnen
- [ ] privates Testdokument autorisiert herunterladen
- [ ] `/verwaltung` bleibt ohne Administrationsrolle gesperrt

### Kombinierte Rolle

Mit `administration` + `board_member`:

- [ ] beide Bereiche erreichbar
- [ ] zulässige Mitgliedschaftsinformationen im vorgesehenen Kontext sichtbar

### Normales Mitglied

- [ ] kein interner Bereich erreichbar
- [ ] normales Portal/Konto funktioniert

## 13. Portal-Einladung und Mail

Mindestens bei Erstabnahme und nach relevanten Identity-/Mailänderungen:

1. Testperson ohne Konto anlegen/verwenden.
2. Portal-Einladung auslösen.
3. Queue-Verarbeitung beobachten.
4. Delivery-Status prüfen.
5. Mail über sicheren Staging-Pfad empfangen.
6. Einladung öffnen.
7. Konto verknüpfen.
8. Login durchführen.
9. Audit-Spur prüfen.

Erwartung:

- kein CLI-/DB-Eingriff nötig
- Token ist nicht wiederverwendbar
- Ablauf/Widerruf verhalten sich wie vorgesehen
- echte Production-Empfänger werden nicht angeschrieben

## 14. Queue-Smoke-Test

- [ ] Worker-Prozess läuft
- [ ] gezielter Mailjob wird angenommen
- [ ] Job verschwindet nach Erfolg aus der Queue
- [ ] Delivery-Status wird fachlich aktualisiert
- [ ] `failed_jobs` kontrolliert geprüft
- [ ] Worker-Restart nach Deployment funktioniert

Zusätzlich einmal absichtlich den Worker stoppen und bestätigen, dass Monitoring/Service-Manager den Ausfall erkennt.

## 15. Scheduler-Smoke-Test

Der Scheduler muss minütlich angestoßen werden; im Repository existiert aktuell mindestens ein stündlicher Cleanup-Job.

Prüfen:

- [ ] Timer/Cron läuft
- [ ] zeigt auf `current`
- [ ] Heartbeat aktuell
- [ ] kein paralleler unerwünschter Scheduler aus altem Release
- [ ] nach Reboot weiterhin aktiv

Für den fachlichen Cleanup kann ein kontrollierter Staging-Datensatz mit passendem Ablaufdatum verwendet werden, sofern dadurch keine Testdaten anderer Prüfungen zerstört werden.

## 16. Privater Storage

Erstdeployment:

- [ ] privates Testdokument hochladen
- [ ] Metadaten im Portal sichtbar
- [ ] autorisierter Download funktioniert
- [ ] direkte öffentliche URL auf Storage funktioniert nicht
- [ ] nicht berechtigte Rolle erhält keinen Download

Release-Persistenztest:

1. Dokument vor Deployment N hochladen.
2. neues Release N+1 deployen.
3. dasselbe Dokument erneut autorisiert abrufen.
4. bestätigen, dass die Datei nicht aus dem alten Release-Verzeichnis stammt.

## 17. Audit und Datenminimierung

Stichprobe:

- [ ] schreibende Verwaltungsaktion erscheint im Audit
- [ ] schreibende Vorstandsaktion erscheint für berechtigte Rolle
- [ ] reiner Admin sieht keine mitgliedschaftssensitiven Audit-Ereignisse
- [ ] direkte sensitive Audit-URL bleibt für nicht berechtigte Rolle gesperrt

## 18. Fehlerseiten und Sicherheitsgrenzen

- [ ] nicht existente Seite liefert erwarteten 404-Pfad
- [ ] direkte unberechtigte interne URL liefert 403/geeignete Ablehnung ohne Datenleck
- [ ] `APP_DEBUG=false` zeigt keine Stacktraces
- [ ] `.env` nicht per HTTP erreichbar
- [ ] privater Storage nicht per HTTP-Verzeichnis erreichbar
- [ ] Server-/Framework-Versionen werden nicht unnötig prominent offengelegt

## 19. Performance-/Ressourcen-Baseline

Vor Produktion mindestens einfache Ausgangswerte dokumentieren:

- RAM-Verbrauch im Leerlauf
- PHP-FPM-Prozessanzahl/-modell
- MySQL-Verbrauch
- Queue-Worker-Verbrauch
- durchschnittliche Responsezeit einfacher Seiten
- freier Speicherplatz
- Größe Datenbank
- Größe private Fachdateien

Ziel ist keine frühe Optimierung, sondern eine messbare Baseline für spätere Abweichungen.

## 20. Reboot-Test

Vor Produktionsfreigabe Staging-VM kontrolliert neu starten.

Danach ohne manuelle Reparatur prüfen:

- [ ] Webserver aktiv
- [ ] PHP-FPM aktiv
- [ ] MySQL aktiv
- [ ] Queue Worker aktiv
- [ ] Scheduler aktiv
- [ ] TLS/Domain erreichbar
- [ ] `current` korrekt
- [ ] private Dateien vorhanden
- [ ] Monitoring meldet wieder gesund

## 21. Backup-/Restore-Probelauf

Vor Produktion vollständig nach `docs/29_BACKUP_RESTORE_RUNBOOK.md` durchführen.

Mindestens:

- [ ] Backup-Set erzeugt
- [ ] Off-Host-Kopie vorhanden
- [ ] Prüfsummen gültig
- [ ] DB restauriert
- [ ] private Dateien restauriert
- [ ] fachliche Konsistenz bestanden
- [ ] Restore-Zeit gemessen
- [ ] Queue/Scheduler kontrolliert wieder gestartet

## 22. Rollback-Probe

Code-Rollback mindestens einmal praktisch testen:

1. Release A aktiv.
2. Release B deployen.
3. Smoke Test durchführen.
4. kontrolliert auf A zurückschalten.
5. Smoke Test wiederholen.

Wichtig: Der Test darf nur mit Datenbankschema funktionieren, das für diese Probe rückwärtskompatibel ist. Ein Symlink-Rollback macht irreversible Migrationen nicht rückgängig.

Protokollieren:

- Ursache der simulierten Rücknahme
- benötigte Zeit
- Datenbankstatus
- Worker-Restart
- Ergebnis

## 23. Erstabnahme Browser/Clients

Nach dem Deployment zusätzlich mindestens:

- Chrome
- Firefox
- Edge
- Safari, sobald verfügbar
- mobiler Viewport

Die vollständige Design-QA wird nicht neu dupliziert, aber Staging muss bestätigen, dass das deployte Asset-/Webserver-Setup dieselben Oberflächen korrekt ausliefert.

## 24. Staging-Abnahmeprotokoll

```text
Datum:
Staging-Domain:
Commit-SHA:
CI-Run:
Security-Run:
Server-Baseline: bestanden/nicht bestanden
Deployment: bestanden/nicht bestanden
Migrationen: bestanden/nicht bestanden
Health Check: bestanden/nicht bestanden
Login/Konto: bestanden/nicht bestanden
2FA: bestanden/nicht bestanden
Verwaltung: bestanden/nicht bestanden
Vorstand: bestanden/nicht bestanden
Rollenabgrenzung: bestanden/nicht bestanden
Mail/Einladung: bestanden/nicht bestanden
Queue: bestanden/nicht bestanden
Scheduler: bestanden/nicht bestanden
Privater Storage: bestanden/nicht bestanden
Audit: bestanden/nicht bestanden
Reboot-Test: bestanden/nicht bestanden
Backup/Restore: bestanden/nicht bestanden
Rollback: bestanden/nicht bestanden
Offene Befunde:
Ergebnis: freigegeben/nicht freigegeben
```

Keine Secrets oder echten personenbezogenen Daten in dieses Protokoll schreiben.

## 25. Gate zu Production

Production wird erst vorbereitet/freigegeben, wenn:

1. Staging mit einem eindeutig identifizierten Release-Kandidaten bestanden ist,
2. Backup/Restore praktisch bestanden ist,
3. Code-Rollback praktisch bestanden ist,
4. Reboot-/Prozessstart bestanden ist,
5. keine offenen kritischen/hohen Betriebsbefunde bestehen,
6. Production-Secrets und Environment-Regeln vorbereitet sind,
7. die relevanten Punkte aus `docs/07_PHASE_7_PRODUKTIV_CHECKLISTE.md` erfüllt sind.
