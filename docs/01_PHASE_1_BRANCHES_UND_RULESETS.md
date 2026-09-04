# Phase 1 – Branches und Rulesets

## Branch-Struktur

### `main`

Zweck:
- stabil
- releasefähig
- Quelle für Produktion

Regeln:
- Pull Request erforderlich
- 1 Approval
- stale Approvals verwerfen
- Approval des letzten reviewbaren Pushes erforderlich
- Conversation Resolution erforderlich
- Branch-Löschung blockieren
- Force Push blockieren
- Status Checks später aktivieren
- Code Scanning später aktivieren
- Code Owners vorerst nicht

### `develop`

Zweck:
- aktive Integration
- Ziel für Features und normale Fixes

Regeln:
- Pull Request erforderlich
- 1 Approval
- stale Approvals verwerfen
- Approval des letzten reviewbaren Pushes erforderlich
- Conversation Resolution erforderlich
- Branch-Löschung blockieren
- Force Push blockieren
- Status Checks später aktivieren
- Code Scanning später aktivieren

### `release/*`

Zweck:
- Release-Kandidaten

Regeln:
- Pull Request erforderlich
- 1 Approval
- stale Approvals verwerfen
- Approval des letzten reviewbaren Pushes erforderlich
- Conversation Resolution erforderlich
- Branch-Löschung blockieren
- Force Push blockieren
- Status Checks später aktivieren
- Code Scanning später aktivieren

### `hotfix/*`

Zweck:
- dringende Produktionsfixes

Regeln:
- Pull Request erforderlich
- 1 Approval
- stale Approvals verwerfen
- Approval des letzten reviewbaren Pushes erforderlich
- Conversation Resolution erforderlich
- Branch-Löschung blockieren
- Force Push blockieren
- Status Checks später aktivieren
- Code Scanning später aktivieren

### `archive/*`

Zweck:
- unveränderliche Referenz

Regeln:
- Restrict updates: aktiv
- Restrict deletions: aktiv
- Block force pushes: aktiv
- Pull Request erforderlich: nein
- Status Checks: nein
- Code Scanning: nein

## Ruleset-Matrix

| Rule | main | develop | release/* | hotfix/* | archive/* |
|---|---:|---:|---:|---:|---:|
| Restrict creations | Nein | Nein | Nein | Nein | Nein |
| Restrict updates | Nein | Nein | Nein | Nein | Ja |
| Restrict deletions | Ja | Ja | Ja | Ja | Ja |
| Require linear history | Nein | Nein | Nein | Nein | Nein |
| Require merge queue | Nein | Nein | Nein | Nein | Nein |
| Require deployments to succeed | Später | Nein | Nein | Nein | Nein |
| Require signed commits | Nein | Nein | Nein | Nein | Nein |
| Require pull request before merging | Ja | Ja | Ja | Ja | Nein |
| Require status checks to pass | Später | Später | Später | Später | Nein |
| Block force pushes | Ja | Ja | Ja | Ja | Ja |
| Require code scanning results | Später | Später | Später | Später | Nein |
| Require code quality results | Nein | Nein | Nein | Nein | Nein |
| Copilot code review automatisch | Nein | Nein | Nein | Nein | Nein |
| Restrict commit metadata | Nein | Nein | Nein | Nein | Nein |
| Restrict branch names | Nein | Nein | Nein | Nein | Nein |

## PR-Unterregeln für main/develop/release/hotfix

- Required approvals: 1
- Dismiss stale pull request approvals: Ja
- Require review from specific teams: Nein
- Require review from Code Owners: vorerst Nein
- Require approval of the most recent reviewable push: Ja
- Require conversation resolution before merging: Ja

## Merge-Methoden

Für `main` und `develop`:
- Squash: Ja
- Merge: Nein bzw. nur bei gewünschter Release-Historie
- Rebase: Nein

Für `release/*` und `hotfix/*`:
- Squash: Ja
- Merge: optional Ja
- Rebase: Nein
