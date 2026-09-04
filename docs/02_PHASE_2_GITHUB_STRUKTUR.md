# Phase 2 – GitHub-Struktur

## Dateien

```text
.github/
├── ISSUE_TEMPLATE/
│   ├── bug_report.yml
│   ├── feature_request.yml
│   ├── security_hardening.yml
│   ├── qa_check.yml
│   ├── deployment_task.yml
│   ├── documentation_task.yml
│   ├── technical_debt.yml
│   └── config.yml
└── pull_request_template.md
```

`CODEOWNERS` wird vorerst übersprungen.

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

### Bereich
- `area: login`
- `area: konto`
- `area: verwaltung`
- `area: personen`
- `area: berechtigungen`
- `area: einladungen`
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

## Milestones

- `v0.1 Betriebsfähigkeit`
- `v0.2 Security-Hardening`
- `v0.3 Staging-Ready`
- `v1.0 Produktionsstart`
- `v1.1 Nachbetrieb / Verbesserungen`
