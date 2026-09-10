# Gesamtplanung – GitHub Repository für aktiven Betrieb

Repository: `VDBS-e-V/vereinsportal`

## Zielbild

Das Repository soll sicher, nachvollziehbar und für tägliche Entwicklung geeignet sein. Alles Reproduzierbare wird versioniert; nicht versionierbare Schutzregeln werden über GitHub Rulesets und Repository-Einstellungen verwaltet.

## Aktueller Stand

Die Entwicklungsbasis für Repository, CI und versionierte Security-Maßnahmen ist hergestellt:

- `main` ist der einzige dauerhafte Integrations- und Release-Branch.
- Änderungen gehen über kurzlebige Arbeitsbranches und Pull Requests nach `main`.
- `main` ist durch ein aktives Ruleset geschützt.
- Squash ist die einzige aktivierte Merge-Methode; gemergte Head-Branches werden automatisch gelöscht.
- CI prüft `Quality`, PHP-Kompatibilität für 8.4.1 und 8.5 sowie statische PHP-Analyse mit Larastan/PHPStan.
- Security prüft Composer, npm, Pull-Request-Abhängigkeiten und High-Confidence-Secrets.
- Dependabot ist für GitHub Actions, Composer und npm konfiguriert.
- Designsystem v1 ist technisch vorbereitet; die manuelle Freigabe nach `docs/09_DESIGN_QA_CHECKLIST.md` ist noch offen.

Nicht automatisch als erledigt gelten GitHub-Security-Schalter, die nicht zuverlässig über den verwendeten Connector verifiziert werden können. Diese werden über die Settings-Checklisten bzw. direkt in GitHub geprüft.

## Phasen

1. Repository-Grundlage und Branch-Modell – **Basis hergestellt**
2. GitHub-Struktur und Zusammenarbeit – **Basis hergestellt**
3. CI / QA – **automatisierte Basis hergestellt**
4. Security – **versionierte Basis hergestellt; manuelle GitHub-Settings teilweise noch zu verifizieren**
5. Deployment-Struktur – **offen**
6. Release-Prozess – **fachlich beschrieben, operativer Probelauf offen**
7. Produktiv-Check – **offen**

Die laufende Entwicklungsreihenfolge steht zusätzlich in `docs/23_DEVELOPMENT_ROADMAP.md`.

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

Für `main` ist das Ruleset `Protect main` aktiv. Es erzwingt insbesondere:

- Pull Request vor Merge
- erforderliche Status Checks
- Conversation Resolution
- Force-Push-/Non-Fast-Forward-Schutz
- Schutz vor Branch-Löschung
- lineare Historie
- Squash als zulässige Merge-Methode

Required Checks:

- `Quality`
- `Static Analysis`
- `Composer Audit`
- `NPM Audit`
- `Dependency Review`
- `Secret Scan`

Approval-Pflicht bleibt bei nur einem verlässlichen Maintainer auf 0. Die Pflicht, den PR-Branch vor dem Merge zwingend auf den neuesten `main`-Stand zu aktualisieren, ist derzeit nicht aktiviert und bleibt eine bewusste offene Entscheidung.

## CI / QA

Versioniert in `.github/workflows/ci.yml`:

- Composer-Validierung und Installation
- Pest
- Pint
- MySQL-8.4-Migrationen
- Node.js 22 / npm
- Vite-Build
- Web-Content-Library-Integritätsprüfung
- PHP-Kompatibilitätschecks für 8.4.1 und 8.5
- statische PHP-Analyse mit Larastan/PHPStan

Die manuelle Design-QA wird getrennt davon nach `docs/09_DESIGN_QA_CHECKLIST.md` durchgeführt.

## Security

Versioniert:

- Composer Audit
- npm Audit
- Dependency Review
- High-Confidence Secret Scan
- Larastan/PHPStan als statische PHP-Analyse
- Dependabot
- `SECURITY.md`

PHP-CodeQL ist nicht Teil der aktuellen Pipeline; die frühere PHP-CodeQL-Konfiguration wurde durch die funktionierende statische PHP-Analyse ersetzt.

Zusätzlich direkt in GitHub verifizieren:

- Dependabot Alerts
- Dependabot Security Updates
- Secret Scanning
- Push Protection
- Private Vulnerability Reporting

## Deployment

Deployment wird erst nach geklärtem Hosting-, Backup- und Rollback-Konzept automatisiert. Production-Secrets gehören ausschließlich in ein geschütztes GitHub Environment oder den Secret Store des Hostings.

## Releases

Releases werden aus einem grünen `main` erzeugt:

`main` → Tag `vX.Y.Z` → GitHub Release → optional Production-Deployment

Vor dem ersten produktiven Release ist ein nachvollziehbarer 0.x-Probelauf vorgesehen.

## Produktiv-Check

Vor dem Produktionsbetrieb müssen CI, Security, Backup, Restore, Rollback, Environment-Schutz, Datenschutzprozesse und produktive Konfiguration separat abgenommen werden. Der konkrete Stand wird ausschließlich in `docs/07_PHASE_7_PRODUKTIV_CHECKLISTE.md` abgehakt.
