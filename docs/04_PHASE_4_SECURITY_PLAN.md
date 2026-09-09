# Phase 4 – Security

## Versionierte Schutzmaßnahmen

### `.github/workflows/security.yml`

- Composer Audit
- npm Audit
- Dependency Review auf Pull Requests
- zusätzlicher High-Confidence Secret Scan

### `.github/workflows/codeql.yml`

- CodeQL für PHP
- Pull Requests und `main`
- wöchentlicher Scan

### `.github/dependabot.yml`

- GitHub Actions
- Composer
- npm
- gruppierte Minor-/Patch-Updates

## GitHub Actions Härtung

- `permissions` explizit und minimal
- Checkout ohne persistierte Credentials
- Third-Party-Actions auf vollständige Commit-SHAs pinnen
- keine Deployment-Secrets in Pull-Request-Workflows
- `pull_request_target` für untrusted Code vermeiden
- Timeouts und Concurrency verwenden

## Repository-Einstellungen

Zusätzlich manuell aktivieren:

- Dependabot Alerts
- Dependabot Security Updates
- Secret Scanning
- Push Protection
- Code Scanning
- Private Vulnerability Reporting

## Security Issues

Öffentliche Issues dürfen keine:

- Passwörter
- Tokens
- private Schlüssel
- echten personenbezogenen Daten
- unmittelbar ausnutzbaren vertraulichen Details

enthalten.

Vertrauliche Schwachstellen ausschließlich über die in `SECURITY.md` beschriebenen privaten Meldewege behandeln.
