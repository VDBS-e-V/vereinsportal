# Phase 1 – Branches und Rulesets

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

## Empfohlenes Ruleset für `main`

- Restrict deletions: Ja
- Block force pushes: Ja
- Require linear history: Ja
- Require pull request before merging: Ja
- Require conversation resolution: Ja
- Require status checks to pass: Ja
- Require branches to be up to date before merging: Ja
- Require signed commits: zunächst optional
- Require merge queue: zunächst Nein
- Require deployments to succeed: erst mit Deployment-Pipeline

### Reviews

Solange nur eine Person zuverlässig maintained, keine Approval-Regel aktivieren, die alle PRs blockiert.

Sobald mindestens zwei Reviewer verfügbar sind:

- Required approvals: 1
- Dismiss stale approvals: Ja
- Require approval of the most recent reviewable push: Ja
- optional Code Owner Review

## Required Status Checks

Nach erfolgreichen Workflow-Läufen mindestens:

- `Quality`
- Security-Checks, die für Pull Requests stabil verfügbar sind

CodeQL kann zusätzlich über Code-Scanning-Regeln verpflichtend gemacht werden.

## Merge-Methoden

Empfohlen:

- Squash Merge: Ja
- Merge Commit: Nein
- Rebase Merge: Nein
- Delete branch after merge: Ja

Damit bleibt `main` linear und die Pull-Request-Historie nachvollziehbar.

## Archive

Falls unveränderliche Referenzbranches benötigt werden, `archive/*` in ein separates Ruleset aufnehmen und Updates, Löschungen sowie Force Pushes blockieren.
