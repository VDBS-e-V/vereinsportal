# Phase 4 – Security

## Status

Die versionierte Security-Basis ist umgesetzt. Die Repository-Schalter für GitHub-eigene Security-Funktionen werden getrennt davon direkt in GitHub verifiziert, weil sie über den verwendeten Connector nicht vollständig zuverlässig auslesbar sind.

Die konkrete manuelle Prüfung und das Nachweisformat stehen in `docs/25_GITHUB_SETTINGS_RUNBOOK.md` und Issue #15.

## Versionierte Schutzmaßnahmen

### `.github/workflows/security.yml`

Aktiv:

- `Composer Audit`
- `NPM Audit`
- `Dependency Review` auf Pull Requests
- `Secret Scan` für High-Confidence-Muster in versionierten Dateien

Die vier Jobs sind Required Status Checks für `main`.

### Statische PHP-Analyse

Die statische PHP-Analyse läuft in `.github/workflows/ci.yml` als Required Check `Static Analysis` mit Larastan/PHPStan über `composer analyse`.

Ein eigener PHP-CodeQL-Workflow ist nicht mehr Teil des Repositories. Die frühere Konfiguration wurde entfernt und durch die funktionierende PHP-Analyse ersetzt.

### `.github/dependabot.yml`

Dependabot ist versioniert für:

- GitHub Actions
- Composer
- npm

Updates laufen gruppiert und regelmäßig. GitHub-eigene Dependabot Alerts und Security Updates sind davon getrennte Repository-Einstellungen und müssen direkt in GitHub verifiziert werden.

## GitHub Actions Härtung

Aktiv bzw. als verbindliche Regel vorgesehen:

- `permissions` explizit und minimal
- Checkout ohne persistierte Credentials
- Third-Party-Actions auf vollständige Commit-SHAs pinnen
- keine Deployment-Secrets in Pull-Request-Workflows
- `pull_request_target` für untrusted Code vermeiden
- Timeouts und Concurrency verwenden

Die Repository-weiten Actions-UI-Einstellungen werden zusätzlich über Issue #14 und `docs/25_GITHUB_SETTINGS_RUNBOOK.md` geprüft.

## Repository-Einstellungen

Direkt in GitHub verifizieren und erst danach in der Produktiv-Checkliste abhaken:

- Dependency Graph
- Dependabot Alerts
- Dependabot Security Updates
- Secret Scanning
- Push Protection
- Generic secret patterns, falls verfügbar
- Validity checks, falls verfügbar
- Private Vulnerability Reporting

Nicht in jedem GitHub-Plan bzw. Repository stehen alle Optionen identisch zur Verfügung. Nicht verfügbare Schalter werden als `nicht verfügbar` dokumentiert und nicht als erfolgreich geprüft ausgegeben.

Für diese Prüfungen stehen außerdem die versionierten Settings-Issue-Formulare unter `.github/ISSUE_TEMPLATE/` zur Verfügung.

## Security Issues

Öffentliche Issues dürfen keine:

- Passwörter
- Tokens
- private Schlüssel
- echten personenbezogenen Daten
- unmittelbar ausnutzbaren vertraulichen Details

enthalten.

Vertrauliche Schwachstellen ausschließlich über die in `SECURITY.md` beschriebenen privaten Meldewege behandeln.

## Abnahmekriterium

Phase 4 gilt vollständig als abgenommen, wenn:

1. `Static Analysis`, `Composer Audit`, `NPM Audit`, `Dependency Review` und `Secret Scan` auf einem Probe-PR grün laufen,
2. die manuellen GitHub-Security-Einstellungen nach `docs/25_GITHUB_SETTINGS_RUNBOOK.md` geprüft und dokumentiert sind,
3. keine vertraulichen Meldungen über öffentliche Issues abgewickelt werden müssen.

Punkt 1 ist technisch hergestellt; Punkt 2 bleibt bis zur manuellen Verifikation offen.
