# Phase 5 – Deployment Planung

## Grundsatz

Deployment wird nicht automatisch an jeden Push auf `main` gekoppelt. Zuerst müssen Hosting, Backup, Rollback und Environment-Schutz verbindlich geklärt sein.

## Staging

Empfohlen:

- manuelles oder freigegebenes Deployment aus geprüftem `main`
- optional Preview-/Staging-Deployment für ausgewählte Pull Requests
- keine Production-Secrets in PR-Workflows

## Produktion

Zielmodell:

`main` → Tag `vX.Y.Z` → GitHub Release → Production-Deployment

## GitHub Environments

Geplant:

- `staging`
- `production`

Für `production`:

- Required Reviewer, sobald organisatorisch möglich
- Prevent self-review, sobald mehrere Reviewer verfügbar sind
- Branch-/Tag-Einschränkungen
- Secrets ausschließlich im Environment

## Geplante Secrets

- `PROD_HOST`
- `PROD_USER`
- `PROD_SSH_KEY`
- `PROD_PATH`

Variable:

- `PROD_URL`

## Geplanter Workflow

`.github/workflows/deploy-production.yml`

Trigger:

- GitHub Release `published`
- optional `workflow_dispatch`

## Vor jeder Automatisierung klären

- Hosting / Servertyp
- Apache oder Nginx
- produktive PHP-Version
- SSH- und Schlüsselverwaltung
- Build-Artefakt oder Build auf Server
- Migrationsstrategie
- Wartungsmodus
- Health Check
- Backup
- Rollback
- Verantwortlichkeiten
