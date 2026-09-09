# Phase 2 – GitHub-Struktur

## Dateien

```text
.github/
├── CODEOWNERS
├── dependabot.yml
├── release.yml
├── pull_request_template.md
├── ISSUE_TEMPLATE/
│   ├── bug_report.yml
│   ├── feature_request.yml
│   ├── security_hardening.yml
│   ├── qa_check.yml
│   ├── deployment_task.yml
│   ├── documentation_task.yml
│   ├── technical_debt.yml
│   └── config.yml
└── workflows/
    ├── ci.yml
    ├── security.yml
    └── codeql.yml
```

`CODEOWNERS` dokumentiert die fachliche Zuständigkeit. Eine verpflichtende Code-Owner-Freigabe wird erst aktiviert, wenn die Teamstruktur das zuverlässig erlaubt.

## Labels

Empfohlene Labels:

### Typ

- `type: bug`
- `type: feature`
- `type: security`
- `type: qa`
- `type: docs`
- `type: refactor`
- `type: deployment`
- `dependencies`

### Bereich

- `area: login`
- `area: konto`
- `area: verwaltung`
- `area: personen`
- `area: berechtigungen`
- `area: datenschutz`
- `area: audit`
- `area: datenbank`
- `area: frontend`
- `area: ci`
- `area: docs`

### Priorität

- `priority: high`
- `priority: medium`
- `priority: low`

### Status

- `status: triage`
- `status: ready`
- `status: in-progress`
- `status: blocked`
- `status: review`
- `status: done`

## Issues

Blank Issues bleiben deaktiviert. Sicherheitslücken werden über Private Vulnerability Reporting gemeldet und nicht als öffentliches Security-Issue.

## Releases

`.github/release.yml` gruppiert automatisch generierte Release Notes anhand der PR-Labels.
