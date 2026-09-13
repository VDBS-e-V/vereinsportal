# Backup- und Restore-Runbook

Dieses Dokument definiert den ersten belastbaren Backup-/Restore-Probelauf für Staging und später Produktion. Es ersetzt keine Entscheidung über Anbieter, Speicherziel oder konkrete Aufbewahrungsfristen; diese bleiben Teil von Issue #19.

Wesentlich ist die fachliche Besonderheit des Portals: Ein vollständiger Wiederherstellungspunkt besteht nicht nur aus der SQL-Datenbank. Private Mitgliedschaftsdokumente liegen im persistenten Laravel-Storage und müssen gemeinsam mit den passenden Datenbankeinträgen wiederherstellbar sein.

## 1. Ziel

Ein Restore gilt erst als erfolgreich, wenn nachgewiesen ist, dass:

- die Anwendung auf dem wiederhergestellten Datenstand startet,
- Datenbank und private Fachdateien zeitlich zusammenpassen,
- relevante Mitgliedschaftsdokumente wieder autorisiert abrufbar sind,
- Benutzer-, Rollen-, Mitgliedschafts- und Audit-Daten konsistent sind,
- Queue/Scheduler nach der Wiederherstellung kontrolliert wieder anlaufen,
- keine Production-Secrets oder Backups in das Repository gelangen.

Ein vorhandenes Backup ohne praktisch getesteten Restore ist kein abgeschlossener Betriebsnachweis.

## 2. Backup-Set statt unabhängiger Einzeldateien

Jeder fachlich verwertbare Sicherungspunkt erhält eine gemeinsame Backup-Set-ID.

Beispiel:

```text
2026-09-13T120000Z-staging
```

Zu einem Set gehören mindestens:

```text
<set-id>/
  manifest.txt
  database/
    <database-backup>
  private-files/
    <storage-backup>
  checksums/
    SHA256SUMS
```

Das Manifest enthält keine Secrets, aber mindestens:

- Umgebung
- Backup-Set-ID
- Start- und Endzeitpunkt
- Anwendung/Commit-SHA
- Datenbanktyp/-version
- verwendetes Backup-Verfahren
- privater Storage-Quellpfad
- Backup-Ziel
- Prüfsummenstatus
- Ergebnis

Ziel: Niemand muss später raten, welcher Dateibestand zu welchem Datenbankstand gehört.

## 3. RPO und RTO ausdrücklich entscheiden

Vor Produktion in #19 festlegen:

- **RPO**: maximal tolerierbarer Datenverlust in Zeit
- **RTO**: maximal tolerierbare Wiederherstellungsdauer

Diese Werte bestimmen unter anderem:

- Backup-Frequenz
- Aufbewahrung
- Off-Host-Replikation
- Automatisierungsgrad
- Monitoring
- notwendigen Restore-Test-Rhythmus

Solange diese Werte noch offen sind, keine fiktive SLA dokumentieren.

## 4. Was gesichert werden muss

### Datenbank

Mindestens:

- vollständiges Anwendungsschema
- alle Fachdaten
- Sessions/Queue/Cache nur soweit für das konkrete Restore-Verfahren sinnvoll; sie sind nicht der fachliche Kern des Backups

Wichtig: Ein Restore soll nicht davon abhängen, dass flüchtige Queue-/Session-Daten unverändert wiederkommen.

### Private Fachdateien

Mindestens der persistente private Laravel-Storage, insbesondere Mitgliedschaftsdokumente.

Nicht als alleinige Sicherung behandeln:

- Release-Verzeichnisse; Code ist über Git/Release reproduzierbar
- generierbare Caches
- `vendor/` oder `node_modules/`

### Konfiguration

Secrets nicht zusammen mit öffentlichen Doku-Artefakten sichern. Für die Wiederherstellung muss aber organisatorisch klar sein, wie folgende Werte aus dem vorgesehenen Secret Store wieder verfügbar werden:

- stabiler `APP_KEY`
- Datenbank-Credentials
- Mail-Credentials
- Domain-/URL-Konfiguration
- sonstige produktive Provider-Secrets

## 5. Backup-Sicherheitsanforderungen

Backup-Ziel:

- außerhalb der produktiven VM oder mindestens zusätzlich Off-Host
- verschlüsselt
- Zugriff nach Least Privilege
- keine öffentliche Freigabe
- Lösch-/Manipulationsschutz soweit praktikabel

VM-Snapshots dürfen ergänzen, ersetzen aber nicht den dokumentierten DB- und Fachdatei-Restore.

Prüfen:

- [ ] Transport verschlüsselt
- [ ] Speicherung verschlüsselt
- [ ] Backup-Credentials getrennt vom normalen App-Zugang
- [ ] Restore-Zugriff dokumentiert
- [ ] Aufbewahrungs-/Löschregeln dokumentiert
- [ ] fehlgeschlagenes Backup löst Alarm aus
- [ ] freier Speicher bzw. Backup-Quota wird überwacht

## 6. Konsistenter Sicherungspunkt

Datenbank und private Dateien können sich während des Backups verändern. Das Verfahren muss deshalb bewusst festlegen, wie ein fachlich konsistenter Sicherungspunkt entsteht.

Zulässige Strategien können sein:

- kurze kontrollierte Schreibpause/Wartungsmodus,
- Storage-/DB-Snapshot-Mechanismus mit gemeinsamer Zeitbasis,
- dokumentierte Reihenfolge mit tolerierter Inkonsistenz und fachlicher Prüfung,
- anderes technisch belegtes Verfahren.

Für die erste Staging-Stufe ist Einfachheit wichtiger als komplizierte Zero-Downtime-Optimierung. Ein kurzer kontrollierter Wartungsmodus ist akzeptabel, wenn er das Verfahren nachvollziehbarer und sicherer macht.

Die gewählte Strategie wird in #19 beschlossen.

## 7. Backup-Ablauf

Generischer Ablauf:

1. Backup-Set-ID erzeugen.
2. aktuelle Umgebung und produktiven Commit-SHA erfassen.
3. freie Kapazität am Ziel prüfen.
4. wenn vorgesehen, Schreibpause/Wartungsmodus aktivieren.
5. Datenbank konsistent sichern.
6. privaten Fachdatei-Storage sichern.
7. Prüfsummen erzeugen.
8. Backup-Manifest schreiben.
9. Backup Off-Host übertragen bzw. dort erzeugen.
10. Prüfsummen am Ziel verifizieren.
11. Schreibpause beenden.
12. Anwendung, Queue und Scheduler prüfen.
13. Backup-Ergebnis protokollieren.
14. Monitoring/Alarmstatus prüfen.

Bei Teilfehlern darf das Set nicht als erfolgreich markiert werden.

## 8. Pre-Deployment-Backup

Vor riskanten Migrationen kann ein zusätzlicher Sicherungspunkt erforderlich sein.

Der Deployment-Prozess muss unterscheiden zwischen:

- regulären planmäßigen Backups,
- zusätzlichem Release-/Migrations-Backup.

Ein Pre-Deployment-Backup ersetzt nicht die reguläre Sicherungsstrategie.

Vor Migration dokumentieren:

```text
Release-SHA:
Migrationen enthalten Schema-/Datenänderungen: ja/nein
Rückwärtskompatibel bewertet: ja/nein
Zusätzliches Backup erforderlich: ja/nein
Backup-Set-ID:
Restore dieses Backup-Typs bereits getestet: ja/nein
```

## 9. Restore nur isoliert beginnen

Ein Restore-Test soll zuerst in einer isolierten Staging-/Restore-Umgebung erfolgen.

Nicht:

- ungeprüft über laufende Produktion restaurieren,
- bestehende produktive Daten überschreiben, nur um den Prozess zu testen,
- Restore-Credentials in öffentliche Logs schreiben.

Vorbereitung:

- [ ] isolierte Ziel-Datenbank vorhanden
- [ ] isolierter privater Storage vorhanden
- [ ] passende `.env` ohne Production-Domain/-Mailrisiko
- [ ] ausgehende Mails kontrolliert bzw. auf sicheren Staging-Pfad begrenzt
- [ ] Queue zunächst gestoppt
- [ ] Scheduler zunächst gestoppt

## 10. Restore-Ablauf

1. gewünschte Backup-Set-ID auswählen.
2. Manifest und Prüfsummen verifizieren.
3. Zielanwendung in kontrollierten Wartungszustand setzen.
4. Queue Worker stoppen.
5. Scheduler stoppen.
6. leere bzw. eindeutig für den Restore vorgesehene Ziel-Datenbank vorbereiten.
7. Datenbankbackup einspielen.
8. privaten Fachdatei-Storage in den vorgesehenen Shared-Bereich wiederherstellen.
9. Eigentümer/Rechte korrigieren.
10. zur gesicherten Anwendungsversion passenden Release aktivieren.
11. stabile Secrets, insbesondere `APP_KEY`, korrekt bereitstellen.
12. Laravel-Konfiguration/Caches kontrolliert neu aufbauen.
13. Anwendung zunächst ohne Queue/Scheduler starten.
14. fachliche Konsistenzprüfung durchführen.
15. Queue starten und prüfen.
16. Scheduler starten und prüfen.
17. Wartungszustand beenden.
18. Smoke Tests durchführen.
19. Restore-Dauer und Ergebnis protokollieren.

Wenn Datenbank und Dateistand nicht zusammenpassen, gilt der Restore als fehlgeschlagen, auch wenn Laravel technisch bootet.

## 11. Fachliche Konsistenzprüfung

Vor dem Backup-Test gezielt Staging-Testdaten anlegen, die später eindeutig geprüft werden können.

Empfohlen:

- mindestens eine Person mit Benutzerkonto,
- mindestens eine aktive Mitgliedschaft,
- mindestens ein privates Mitgliedschaftsdokument,
- mindestens ein Zustimmungsnachweis,
- nachvollziehbarer Audit-Eintrag,
- mindestens eine bekannte Mail-/Delivery-Spur,
- Testdaten ohne echte personenbezogene Informationen.

Nach Restore prüfen:

- [ ] Person vorhanden
- [ ] Konto korrekt verknüpft
- [ ] Mitgliedschaft vorhanden
- [ ] Rollen-/Capability-Verhalten plausibel
- [ ] Mitgliedschaftsdokument-Metadaten vorhanden
- [ ] physische Datei vorhanden
- [ ] autorisierter Download funktioniert
- [ ] unautorisierter Download bleibt gesperrt
- [ ] Zustimmungsnachweis vorhanden
- [ ] Audit-Ereignis vorhanden
- [ ] keine offensichtlichen verwaisten Testdateien

## 12. Datei-/DB-Abgleich

Der erste Restore-Test muss ausdrücklich nachweisen, dass Referenzen und Dateien zusammenpassen.

Mindestens stichprobenartig:

- DB-Eintrag verweist auf vorhandene Datei,
- Dateigröße/Dateiname bzw. gespeicherte Metadaten sind plausibel,
- die Anwendung kann die Datei durch ihren autorisierten Controller ausliefern,
- der Webserver kann den privaten Pfad nicht direkt öffentlich ausliefern.

Wenn später ein automatischer Integritätscheck für Storage-Referenzen eingeführt wird, soll er Teil des Restore-Smoke-Tests werden.

## 13. Queue und Scheduler nach Restore

Flüchtige Jobs dürfen nach einem Restore nicht unkontrolliert doppelt ausgeliefert werden.

Vor Wiederanlauf prüfen:

- welche `jobs`/`failed_jobs` wurden mit restauriert,
- ob alte Mailjobs noch fachlich ausgeliefert werden dürfen,
- ob idempotente/unique Jobs korrekt reagieren,
- ob Scheduler-Aufgaben durch den zurückgesetzten Datenstand erneut ausgelöst werden könnten.

Für Produktion dafür eine explizite Entscheidung in #19 dokumentieren.

## 14. Mail-Sicherheit beim Restore-Test

Restore-Staging darf keine echten historischen Mails an reale Empfänger versenden.

Geeignete Maßnahmen:

- Staging-Mailprovider/Sandbox,
- kontrollierte Empfängerdomain,
- ausgehenden Worker zunächst gestoppt halten,
- vorhandene restaurierte Jobs vor Start bewerten.

Ein Restore-Test, der unbeabsichtigt reale Mails versendet, gilt als Betriebsfehler.

## 15. Restore-Smoke-Test

Mindestens:

- [ ] Startseite/Health Check erreichbar
- [ ] Login mit Staging-Testkonto
- [ ] Konto-/Portalbereich erreichbar
- [ ] Verwaltung mit passender Rolle
- [ ] Vorstand mit passender Rolle
- [ ] private Mitgliedschaftsdokumente abrufbar
- [ ] nicht berechtigte Rolle erhält keinen Zugriff
- [ ] Datenbank-Schreibvorgang in Staging möglich
- [ ] Queue verarbeitet gezielten neuen Testjob
- [ ] Scheduler-Heartbeat vorhanden
- [ ] Logs ohne kritische Restore-Fehler

## 16. Erfolgskriterien

Ein Restore-Probelauf gilt als bestanden, wenn:

1. genau benanntes Backup-Set erfolgreich wiederhergestellt wurde,
2. Prüfsummen vor Restore gültig waren,
3. Anwendung mit dem vorgesehenen Release bootet,
4. Fachdateien und Datenbank konsistent sind,
5. Berechtigungsgrenzen weiterhin greifen,
6. Queue und Scheduler kontrolliert wieder laufen,
7. keine reale unerwünschte Mail ausgelöst wurde,
8. gemessene Restore-Dauer dokumentiert wurde,
9. alle Abweichungen als Ticket erfasst sind.

## 17. Protokollvorlage

```text
Umgebung:
Backup-Set-ID:
Quell-Commit:
Restore-Commit/Release:
DB-Version:
Backup gestartet:
Backup beendet:
Restore gestartet:
Restore beendet:
Gemessene Restore-Dauer:
Prüfsummen: bestanden/nicht bestanden
DB-Restore: bestanden/nicht bestanden
Private Dateien: bestanden/nicht bestanden
Fachliche Konsistenz: bestanden/nicht bestanden
Autorisierter Dokumentzugriff: bestanden/nicht bestanden
Unautorisierter Zugriff blockiert: ja/nein
Queue: bestanden/nicht bestanden
Scheduler: bestanden/nicht bestanden
Mail-Sicherheit: bestanden/nicht bestanden
Offene Befunde:
Ergebnis: bestanden/nicht bestanden
```

Keine Secrets, personenbezogenen Testdaten oder privaten Dokumentinhalte in dieses Protokoll aufnehmen.

## 18. Produktionsfreigabe

Vor Produktion muss mindestens ein vollständiger Restore-Probelauf in Staging bestanden sein. Zusätzlich müssen RPO, RTO, Backup-Frequenz, Aufbewahrung, Off-Host-Ziel, Alarmierung und Verantwortlichkeiten in Issue #19 ausdrücklich entschieden sein.
