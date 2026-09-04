# Phase 5 – Deployment Planung

## Zielmodell

### Staging

`develop` → automatisches Deployment nach Staging

### Produktion

`main` → GitHub Release / Tag `v*` → Production-Deployment

Empfehlung:
Kein sofortiges Production-Deployment bei jedem beliebigen Push auf `main`.

## GitHub Environments

Anlegen:

- `staging`
- `production`

## Production-Schutz

- Required Reviewer
- Prevent self-review
- Branch / Tag-Einschränkung
- Secrets nur im Production Environment

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

## Deployment per SSH / rsync

Möglicher Ablauf:

1. Checkout
2. PHP / Composer vorbereiten
3. QA ausführen
4. SSH vorbereiten
5. Dateien synchronisieren
6. `.env` nicht überschreiben
7. Produktions-Dependencies installieren
8. Health Check
9. Deployment als erfolgreich markieren

## Rollback

Vor Aktivierung des Auto-Deployments muss definiert werden:

- welches Release zuletzt stabil war
- wie Dateien zurückgerollt werden
- wie DB-Migrationen zurückgerollt werden
- wo Backups liegen
- wer Rollback auslösen darf

## Offene technische Klärungen

- Hosting / Servertyp
- Apache oder Nginx
- PHP-Version
- SSH-Zugang für GitHub Actions
- Staging vorhanden?
- Composer auf Server oder Build-Artefakt?
- DB-Migrationsstrategie
- Release-basiertes Deployment oder Merge-Deployment
