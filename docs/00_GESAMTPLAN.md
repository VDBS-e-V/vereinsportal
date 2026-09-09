# Gesamtplanung – GitHub Repository für aktiven Betrieb

Repository: `VDBS-e-V/vereinsportal`

## Zielbild

Das Repository soll sicher, nachvollziehbar und für tägliche Entwicklung geeignet sein. Alles Reproduzierbare wird versioniert; nicht versionierbare Schutzregeln werden anschließend als GitHub Rulesets und Repository-Einstellungen aktiviert.

## Phasen

1. Repository-Grundlage und Branch-Modell
2. GitHub-Struktur und Zusammenarbeit
3. CI / QA
4. Security
5. Deployment-Struktur
6. Release-Prozess
7. Produktiv-Check

## Branch-Modell

Dauerhafter Branch:

- `main` – geprüfter Integrations- und Release-Branch

Kurzlebige Branches:

- `feature/*`
- `fix/*`
- `security/*`
- `refactor/*`
- `docs/*`
- `hotfix/*`

Normale Entwicklung:

`Arbeitsbranch` → Pull Request → `main` → Squash Merge

Ein dauerhafter `develop`-Branch wird nicht verwendet.

## GitHub-Schutz

Für `main` wird ein Ruleset vorgesehen mit:

- Pull Request erforderlich
- erforderliche Status Checks
- Conversation Resolution
- Force Push blockieren
- Branch-Löschung blockieren
- lineare Historie
- optional Approval-Pflicht, sobald zuverlässig mehrere Reviewer verfügbar sind

## CI / QA

Versioniert in `.github/workflows/ci.yml`:

- Composer-Validierung und Installation
- Pest
- Pint
- MySQL-Migrationen
- Node/npm
- Vite-Build
- Designsystem-Integritätsprüfung
- PHP-Kompatibilitätscheck

## Security

Versioniert:

- Composer Audit
- npm Audit
- Dependency Review
- Secret Scan
- CodeQL
- Dependabot
- `SECURITY.md`

Zusätzlich über GitHub Settings aktivieren:

- Secret Scanning
- Push Protection
- Dependabot Alerts / Security Updates
- Code Scanning
- Private Vulnerability Reporting

## Deployment

Deployment wird erst nach geklärtem Hosting- und Rollback-Konzept automatisiert. Production-Secrets gehören ausschließlich in ein geschütztes GitHub Environment oder den Secret Store des Hostings.

## Releases

Releases werden aus einem grünen `main` erzeugt:

`main` → Tag `vX.Y.Z` → GitHub Release → optional Production-Deployment

## Produktiv-Check

Vor dem Produktionsbetrieb müssen CI, Security, Backup, Rollback, Environment-Schutz, Datenschutzprozesse und produktive Konfiguration separat abgenommen werden.
