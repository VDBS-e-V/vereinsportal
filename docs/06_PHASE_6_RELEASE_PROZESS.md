# Phase 6 – Release-Prozess

## Status

Der fachliche Release-Prozess ist definiert. Ein produktiver Release bleibt jedoch bis zur erfolgreichen Beta-Abnahme, geklärter Betriebsarchitektur, erfolgreichem Staging und einem dokumentierten Release-/Restore-Probelauf gesperrt.

Die Betriebsentscheidungen werden in Issue #19 und `docs/27_OPERATIONS_DECISION_MATRIX.md` vorbereitet. Die produktive Abnahme steht in `docs/07_PHASE_7_PRODUKTIV_CHECKLISTE.md`.

## Versionierung

Semantic Versioning:

- `v0.1.0`
- `v0.2.0`
- `v1.0.0`

Vor `v1.0.0` sind 0.x-Releases ausdrücklich für kontrollierte Staging-/Betriebsproben vorgesehen.

## Voraussetzungen für einen Release-Kandidaten

Ein Commit ist erst Release-Kandidat, wenn:

- er auf `main` liegt,
- die verpflichtenden CI- und Security-Checks grün sind,
- keine offenen Blocker aus der fachlichen Abnahme bestehen,
- notwendige Migrationen und Betriebsänderungen dokumentiert sind,
- das Changelog für den Release geprüft ist,
- der konkrete Commit-SHA feststeht.

Ein grünes `main` allein ist keine Produktionsfreigabe.

## Staging vor Produktion

Vor dem ersten produktiven Release und nach relevanten Betriebsänderungen muss derselbe Release-Kandidat in Staging geprüft werden.

Mindestens:

1. Deployment aus bekanntem grünen Commit/Artefakt.
2. Migrationen ausführen.
3. Queue Worker und Scheduler prüfen.
4. Login, Konto, Verwaltung/Vorstand und Mailpfad testen.
5. privaten Mitgliedschaftsdokument-Zugriff prüfen.
6. Health Check und Monitoring prüfen.
7. Rollback des Codes praktisch testen.
8. Restore von Datenbank und privaten Fachdateien praktisch testen.

Der Release-Kandidat darf zwischen erfolgreicher Staging-Abnahme und Production-Deployment nicht stillschweigend neu gebaut oder verändert werden.

## Normaler Release-Ablauf

1. Änderungen per Pull Request nach `main` integrieren.
2. `main` muss alle verpflichtenden CI- und Security-Checks bestehen.
3. Release-Kandidat und Commit-SHA festlegen.
4. `CHANGELOG.md` und Release-relevante Dokumentation prüfen.
5. Staging mit genau diesem Release-Kandidaten erfolgreich durchlaufen.
6. Tag `vX.Y.Z` auf dem freigegebenen Commit erzeugen.
7. GitHub Release veröffentlichen und Release Notes prüfen.
8. Freigegebenes Production-Deployment auslösen.
9. Vor riskanten Migrationen den vorgesehenen Backup-/Restore-Punkt sicherstellen.
10. Deployment mit kontrollierten Migrationen, Cache-Schritten und Worker-Restart durchführen.
11. Health Check und definierte Smoke Tests ausführen.
12. Monitoring, Queue und Scheduler nach dem Deployment kontrollieren.
13. Deployment-Ergebnis und tatsächlich produktiven Commit-SHA dokumentieren.

Ein dauerhafter `develop`- oder zwingender `release/*`-Branch ist nicht erforderlich.

## Migrationen und Daten

Datenbankänderungen sind Teil des Releases, aber nicht automatisch durch einen Code-Rollback rückgängig gemacht.

Deshalb:

- Migrationen möglichst rückwärtskompatibel gestalten,
- destructive Änderungen nicht mit einem ungetesteten Sofort-Rollback kombinieren,
- riskante Änderungen nur mit dokumentiertem Backup-/Restore-Punkt ausführen,
- fehlgeschlagene Migrationen als Deployment-Fehler behandeln und nicht blind fortsetzen.

Private Mitgliedschaftsdokumente sind persistente Fachdateien und gehören nicht in ein Release-Verzeichnis. Releases dürfen diese Dateien weder überschreiben noch verlieren.

## Rollback

Code-Rollback und Daten-Restore sind unterschiedliche Vorgänge.

### Code-Rollback

Bei einem atomischen Release-Modell bevorzugt auf den letzten bekannten funktionierenden Release zurückschalten und anschließend Worker/Health erneut prüfen.

### Daten-Restore

Nur verwenden, wenn fachlich erforderlich. Dabei müssen Datenbankstand und private Mitgliedschaftsdokumente konsistent zusammen wiederhergestellt werden.

Ein Restore ist kein spontaner Production-Schritt, sondern muss vorher in Staging praktisch erprobt sein.

## Release-Branch bei Bedarf

Für längere Stabilisierung kann ausnahmsweise `release/*` verwendet werden. Darauf nur:

- Release-Bugfixes
- Dokumentation
- Changelog
- Versionsnummern
- Deployment-Vorbereitung

Keine neuen größeren Features.

## Hotfix

Dringende Korrektur vom aktuellen `main` in `hotfix/*`, vollständige Checks, Pull Request zurück nach `main`, danach neues Patch-Release.

Auch ein Hotfix überspringt nicht:

- Required Checks,
- notwendige Migrations-/Backup-Bewertung,
- Health Check,
- Smoke Test.

Nur wenn der Produktionsausfall eine verkürzte Staging-Prüfung erzwingt, wird diese Abweichung ausdrücklich dokumentiert und die ausgelassene Probe unmittelbar nachgeholt.

## Nachweis pro produktivem Release

Mindestens dokumentieren:

```text
Version:
Commit-SHA:
Staging-Abnahme:
Production-Deployment-Zeitpunkt:
Migrationen:
Backup-/Restore-Punkt:
Smoke-Test:
Monitoring nach Deployment:
Rollback erforderlich: ja/nein
Besondere Abweichungen:
```
