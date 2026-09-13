# Operations Readiness Map

Dieses Dokument ist der Einstiegspunkt für die Betriebsphase nach der funktionalen Beta. Es ersetzt keine Detailcheckliste, sondern ordnet die vorhandenen Runbooks in die richtige Reihenfolge ein.

## Aktueller Status

Die Anwendung ist technisch noch **nicht** für Staging oder Produktion freigegeben.

Bereits festgelegt:

- Hosting-Anbieter: STRATO
- Servermodell: eigene VM/VPS
- Laravel-/PHP-Basis
- MySQL 8.4 als aktuelle CI-Referenz
- Queue Worker und Scheduler sind erforderlich
- private Mitgliedschaftsdokumente benötigen persistenten Storage
- Backup/Restore muss Datenbank und private Fachdateien gemeinsam behandeln

Noch offen:

- funktionale Beta-Abnahme
- Design-/Accessibility-Abnahme
- finales GitHub-Repository-/Actions-/Security-Gate
- konkrete Betriebsentscheidungen aus #19
- Staging-Aufbau und -Abnahme
- Restore-/Rollback-Probelauf
- Production-Freigabe

## Dokumente und Zweck

### `docs/05_PHASE_5_DEPLOYMENT_PLAN.md`

Belegte Laufzeitanforderungen und grundlegendes Deployment-Zielbild.

### `docs/27_OPERATIONS_DECISION_MATRIX.md`

Offene Betriebsentscheidungen mit empfohlenen Startvarianten. Empfehlungen sind keine Beschlüsse, bis #19 sie ausdrücklich dokumentiert.

### `docs/28_SERVER_BASELINE_AND_BOOTSTRAP.md`

Prüfbare Mindestbasis für eine spätere Staging-/Production-VM: Benutzer/Rechte, PHP, Datenbank, Storage, Queue, Scheduler, Mail, TLS, Logging und Härtung.

### `docs/29_BACKUP_RESTORE_RUNBOOK.md`

Backup-Set-Modell und praktischer Restore-Probelauf für SQL-Daten und private Mitgliedschaftsdokumente, einschließlich fachlicher Konsistenzprüfung.

### `docs/30_STAGING_PREFLIGHT_AND_SMOKE_TEST.md`

Start-Gate, Deployment-Reihenfolge und vollständige Staging-Smoke-Tests für Login, 2FA, Rollen, Verwaltung/Vorstand, Mail, Queue, Scheduler, Storage, Audit, Reboot, Restore und Rollback.

### `docs/06_PHASE_6_RELEASE_PROZESS.md`

Release-Kandidat, Staging-vor-Production, Migrationen und Release-/Rollback-Grundsätze.

### `docs/07_PHASE_7_PRODUKTIV_CHECKLISTE.md`

Verbindliche letzte Checkliste vor Produktionsbetrieb. Nur tatsächlich verifizierte Punkte werden abgehakt.

## Reihenfolge der Gates

```text
#13 / #14 / #15
Repository-, Actions- und Security-Gate
        |
        +------------------+
        |                  |
      #16                #22
Design-QA         Funktionale Beta
        |                  |
        +--------+---------+
                 |
                #19
      Betriebsentscheidungen
                 |
     Server-Baseline / Bootstrap
                 |
          erstes Staging
                 |
    Staging-Smoke + Reboot-Test
                 |
       Backup/Restore-Test
                 |
          Rollback-Test
                 |
        0.x Release-Probe
                 |
       Produktions-Checkliste
                 |
             Production
```

Staging darf nicht genutzt werden, um ungelöste Beta-/Repository-Gates stillschweigend zu umgehen.

## Was vor `deploy-staging.yml` entschieden sein muss

Mindestens:

- Betriebssystem
- Webserver
- produktive PHP-Linie
- Datenbank/Version
- Queue-Prozessmanager
- Scheduler-Mechanismus
- Mail-Provider
- privates Storage-Modell
- Build-Artefakt vs. Server-Build
- Deployment-Transport
- Secret-/Credential-Handling
- Migrations-/Maintenance-Strategie
- Backup-Ziel, Frequenz und Aufbewahrung
- Restore-Verfahren
- Monitoring und Alarmierung
- Verantwortlichkeiten

Der Workflow automatisiert danach diese Entscheidungen; er trifft sie nicht selbst.

## Was vor Production praktisch bewiesen sein muss

Mindestens:

- Staging-Deployment reproduzierbar
- exakter Commit/Release nachvollziehbar
- Migrationen erfolgreich
- Web/PHP/DB/Worker/Scheduler überleben Reboot
- Queue und Mailpfad funktionieren
- private Mitgliedschaftsdokumente überleben Release-Wechsel
- Berechtigungsgrenzen funktionieren in der deployten Umgebung
- Backup-Set erfolgreich erzeugt
- DB und Fachdateien gemeinsam erfolgreich restauriert
- fachliche Konsistenz nach Restore bestätigt
- Code-Rollback praktisch getestet
- Monitoring erkennt zentrale Ausfälle
- keine offenen kritischen/hohen Betriebsbefunde

## Nachweisprinzip

Für jeden manuellen Gate-Schritt dokumentieren:

```text
Datum:
Umgebung:
Commit/Release:
Prüfung:
Erwartung:
Ergebnis:
Abweichungen/Tickets:
Geprüft durch:
```

Keine Secrets, Zugangsdaten oder echten personenbezogenen Inhalte in öffentliche Issues oder versionierte Nachweise schreiben.

## Nächster Übergang

Solange #13/#14/#15/#16/#22 noch offen sind, dient dieser Dokumentensatz ausschließlich der Vorbereitung. Nach deren Abschluss wird #19 aktiv entschieden. Erst danach beginnt die konkrete Staging-Infrastruktur und anschließend die Implementierung von `deploy-staging.yml`.
