# Phase 1 – Branches und Rulesets

## Status

Die Phase-1-Basis ist umgesetzt. `main` ist der einzige dauerhafte Entwicklungs- und Release-Branch und wird durch das aktive Ruleset `Protect main` geschützt.

## Branch-Struktur

### `main`

`main` ist der einzige dauerhafte Entwicklungs- und Release-Branch.

Zweck:

- geprüfter Integrationsstand
- Ausgangspunkt für neue Arbeit
- Grundlage für Releases
- grundsätzlich releasefähig

### Arbeitsbranches

Kurzlebig:

- `feature/*`
- `fix/*`
- `security/*`
- `refactor/*`
- `docs/*`
- `hotfix/*`

Alle starten von aktuellem `main` und werden per Pull Request nach `main` integriert.

## Aktives Ruleset `Protect main`

Aktuell aktiv:

- Restrict deletions: Ja
- Block force pushes / non-fast-forward: Ja
- Require linear history: Ja
- Require pull request before merging: Ja
- Require conversation resolution: Ja
- Require status checks to pass: Ja
- Required approvals: 0
- Allowed merge method im Ruleset: Squash
- Ruleset-Bypass: keiner

Der Probe-PR für die Designsystem-Dokumentation hat bestätigt, dass eine offene Review-Conversation den Merge tatsächlich blockiert und erst nach Auflösung gemergt werden kann.

### Bewusst noch offen

- Require branches to be up to date before merging / strict status checks: derzeit Nein
- Require signed commits: optional
- Require merge queue: Nein
- Require deployments to succeed: erst mit Deployment-Pipeline

Die Update-Branch-Funktion des Repositories ist aktiviert, obwohl ein Update vor Merge derzeit nicht zwingend vorgeschrieben ist.

## Reviews

Solange nur eine Person zuverlässig maintained, bleibt die Approval-Pflicht bei 0, damit Pull Requests nicht organisatorisch blockiert werden.

Sobald mindestens zwei Reviewer zuverlässig verfügbar sind, erneut prüfen:

- Required approvals: 1
- Dismiss stale approvals
- Require approval of the most recent reviewable push
- optional Code Owner Review

## Required Status Checks

Für `main` sind aktuell verpflichtend:

- `Quality`
- `Static Analysis`
- `Composer Audit`
- `NPM Audit`
- `Dependency Review`
- `Secret Scan`

PHP-Kompatibilität für 8.4.1 und 8.5 läuft zusätzlich in CI, ist aber nicht als eigener Required Check eingetragen.

## Merge-Methoden

Repositoryweit aktiv:

- Squash Merge: Ja
- Merge Commit: Nein
- Rebase Merge: Nein
- Delete branch after merge: Ja
- Allow update branch: Ja

Damit bleibt `main` linear und die Pull-Request-Historie nachvollziehbar.

## Archive

Falls unveränderliche Referenzbranches benötigt werden, `archive/*` in ein separates Ruleset aufnehmen und Updates, Löschungen sowie Force Pushes blockieren.
