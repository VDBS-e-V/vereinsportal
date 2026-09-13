# GitHub-Settings: manuelles Abschluss-Runbook

Dieses Runbook bündelt die wenigen Repository-Einstellungen, die über den verbundenen GitHub-Zugriff nicht zuverlässig gelesen oder geändert werden können. Es ist die Ausführungsanleitung für Issues #13, #14 und #15.

Wichtig: Keine Tokens, Secrets, Schwachstellendetails oder vertraulichen Werte in Issues oder Screenshots veröffentlichen. Für den Nachweis reichen Einstellung, Sollwert, Datum und Ergebnis.

## 1. `Protect main` finalisieren (#13)

Pfad in GitHub:

`Settings` → `Rules` → `Rulesets` → `Protect main`

Bereits technisch verifiziert sind PR-Pflicht, lineare Historie, Conversation Resolution, Squash-only, Lösch-/Force-Push-Schutz, Required Checks und fehlende Bypässe.

Noch zu erledigen:

- Ruleset bearbeiten.
- Im Bereich der Required Status Checks die Option **Require branches to be up to date before merging** aktivieren.
- Ruleset speichern.

Erwarteter technischer Zustand danach:

- `strict_required_status_checks_policy = true`
- weiterhin genau die vorgesehenen Required Checks:
  - `Quality`
  - `Static Analysis`
  - `Composer Audit`
  - `NPM Audit`
  - `Dependency Review`
  - `Secret Scan`

### Probe-PR für den Abschluss

Mit einem kleinen Dokumentations-PR prüfen:

1. PR gegen einen aktuellen `main` öffnen und Checks grün werden lassen.
2. Danach `main` durch einen anderen Merge weiterbewegen.
3. Prüfen, dass der ältere PR nicht direkt mergebar bleibt, sondern zuerst aktualisiert werden muss.
4. Eine Review-Conversation öffnen und prüfen, dass sie den Merge blockiert.
5. Prüfen, dass nur Squash als Merge-Methode zulässig ist.
6. Nach dem Merge prüfen, dass der Head-Branch automatisch gelöscht wird.

Erst danach #13 schließen.

## 2. Actions-Einstellungen prüfen (#14)

Pfad:

`Settings` → `Actions` → `General`

Die versionierten Workflows verwenden bereits `permissions: contents: read`, Checkout ohne persistente Credentials und vollständig gepinnte Action-SHAs. Das Runbook prüft nur die Repository-weiten UI-Einstellungen.

### Actions permissions

Prüfen und dokumentieren:

- GitHub-eigene Actions sind erlaubt.
- Externe Actions sind nur so weit erlaubt, wie sie tatsächlich gebraucht werden.
- Aktuell benötigte externe Action: `shivammathur/setup-php`.
- Es gibt keine unnötig breite Freigabe für beliebige Drittanbieter-Actions, sofern GitHub eine engere Konfiguration zulässt.

### Fork pull request workflows

Für externe Fork-PRs sicherstellen, dass unbekannter Code nicht ohne die gewünschte Freigabe mit unnötigen Rechten ausgeführt wird.

Zu dokumentieren:

- tatsächlicher Approval-Modus,
- wer Workflows freigeben darf,
- ob dies zur Maintainer-Situation des Repositories passt.

### Workflow permissions

Zielzustand:

- Standard-`GITHUB_TOKEN` ist read-only bzw. so restriktiv wie möglich.
- Die Option, mit GitHub Actions Pull Requests zu erstellen oder zu genehmigen, ist deaktiviert, solange kein dokumentierter Workflow sie benötigt.

### Retention

Artifact-/Log-Retention bewusst festlegen und den Wert in #14 dokumentieren. Der konkrete Zeitraum ist eine Betriebsentscheidung; er soll nicht zufällig auf einem GitHub-Default beruhen.

Nach Änderungen einen normalen PR-Workflow vollständig durchlaufen lassen und #14 erst schließen, wenn CI und Security weiterhin grün sind.

## 3. Security and quality prüfen (#15)

Je nach GitHub-Oberfläche liegt der Bereich unter `Settings` → `Security` bzw. `Code security and analysis` / `Security and quality`.

Prüfen:

- Dependency Graph: ON
- Dependabot Alerts: ON
- Dependabot Security Updates: ON
- Secret Scanning: ON
- Push Protection: ON
- Generic secret patterns: ON, falls für das Repository verfügbar
- Validity checks: ON, falls verfügbar
- Private Vulnerability Reporting: ON

Nicht jeder Schalter ist in jedem Plan oder Repository identisch benannt/verfügbar. Nicht vorhandene Optionen als `nicht verfügbar` dokumentieren statt sie als bestanden zu markieren.

PHP-CodeQL gehört aktuell bewusst nicht zum Zielbild. Die statische PHP-Analyse erfolgt über den Required Check `Static Analysis` mit Larastan/PHPStan.

## 4. Nachweisformat

Für jeden der drei Issues genügt ein kompakter Abschlusskommentar:

```text
Datum:
Geprüft von:
Bereich:

- Einstellung: Sollwert -> Istwert -> OK/Abweichung
- Einstellung: Sollwert -> Istwert -> OK/Abweichung

Änderungen vorgenommen:
Abschluss-Probe:
Ergebnis:
```

Bei Abweichungen zuerst korrigieren oder die bewusste Abweichung begründen. Erst danach das jeweilige Issue schließen.

## 5. Reihenfolge

Empfohlen:

1. #13 `strict` aktivieren und Probe-PR durchführen.
2. #14 Actions Settings prüfen.
3. #15 Security Settings prüfen.
4. Einen abschließenden kleinen PR als gemeinsame Smoke-Probe verwenden.
5. #13, #14 und #15 jeweils nur bei dokumentiertem Istzustand schließen.
