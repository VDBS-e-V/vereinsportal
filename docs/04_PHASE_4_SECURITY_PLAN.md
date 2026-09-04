# Phase 4 – Security Planung

## GitHub-Funktionen

Aktivieren:

- Dependabot Alerts
- Dependabot Security Updates
- Dependabot Version Updates
- Secret Scanning
- Push Protection
- Code Scanning / CodeQL
- Private Vulnerability Reporting

## Workflows

Geplant:

- `.github/workflows/security.yml`
- `.github/workflows/dependency-review.yml`

## Checks

- CodeQL
- Composer Audit
- Dependency Review
- PHP Syntax / statische Prüfungen nach Bedarf

## GitHub Actions Härtung

- `permissions` explizit definieren
- `GITHUB_TOKEN` minimal berechtigen
- Drittanbieter-Actions möglichst auf Commit-SHA pinnen
- keine Secrets in untrusted Pull Requests
- `pull_request_target` möglichst vermeiden
- Deployment-Secrets nur in Environments
- Production nur mit Approval

## Security Issues

Öffentliche Issue-Templates dürfen keine:
- Passwörter
- Tokens
- personenbezogenen Daten
- verwertbaren Exploit-Details

enthalten.

Vertrauliche Sicherheitslücken über GitHub Security Advisories / Private Vulnerability Reporting.
