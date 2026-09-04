# Gesamtplanung – GitHub Repository für aktiven Betrieb

Repository: `VDBS-e-V/Portal-Neu`

## Zielbild

Das Repository wird für einen geregelten, sicheren und nachvollziehbaren aktiven Betrieb vorbereitet.

Geplante Bereiche:

1. Repository-Grundlage und Branch-Modell
2. GitHub-Struktur mit Issue- und PR-Templates
3. CI / QA
4. Security
5. Deployment-Struktur
6. Release-Prozess
7. Produktiv-Check

---

# Phase 1 – Repository-Grundlage

## Ziel-Branches

- `main` – stabiler, releasefähiger und produktionsnaher Branch
- `develop` – Integrationsbranch für aktive Entwicklung
- `archive/reset-base-skeleton` – eingefrorene Referenz des bisherigen Ausgangsstands

Zusätzliche Branches bei Bedarf:

- `feature/*`
- `fix/*`
- `security/*`
- `release/*`
- `hotfix/*`
- `archive/*`

## Arbeitsfluss

Normale Entwicklung:

`feature/*` / `fix/*` / `security/*` → Pull Request → `develop`

Release:

`develop` → `release/x.y.z` → `main` → Tag / Release

Hotfix:

`main` → `hotfix/x.y.z-thema` → `main` → Rückmerge nach `develop`

## Merge-Strategie

- Feature-/Fix-/Security-Branches: bevorzugt Squash Merge
- Release-/Hotfix-Branches: Merge Commit oder Squash je nach gewünschter Historie
- Keine direkten Pushes auf `main` und `develop`

---

# Phase 2 – GitHub-Struktur

Geplante Dateien:

`.github/ISSUE_TEMPLATE/`
- `bug_report.yml`
- `feature_request.yml`
- `security_hardening.yml`
- `qa_check.yml`
- `deployment_task.yml`
- `documentation_task.yml`
- `technical_debt.yml`
- `config.yml`

Zusätzlich:
- `.github/pull_request_template.md`

`CODEOWNERS` wird vorerst nicht verwendet, da das Team aktuell klein ist.

---

# Phase 3 – CI / QA

Geplant:

- `.github/workflows/ci.yml`
- `composer validate`
- `composer install`
- PHPUnit
- `php tools/qa/run_all.php`
- Required Status Checks für `main` und `develop`

---

# Phase 4 – Security

Geplant:

- `.github/workflows/security.yml`
- Composer Audit
- CodeQL
- Dependency Review
- Dependabot
- Secret Scanning
- Push Protection
- `SECURITY.md`
- minimale `GITHUB_TOKEN`-Berechtigungen
- Third-Party-Actions möglichst auf Commit-SHA pinnen
- Production-Secrets nur über GitHub Environments

---

# Phase 5 – Deployment

GitHub Environments:

- `staging`
- `production`

Geplante Secrets / Variablen:

- `PROD_HOST`
- `PROD_USER`
- `PROD_SSH_KEY`
- `PROD_PATH`
- `PROD_URL`

Empfohlenes Zielmodell:

`develop` → automatisches Staging-Deployment

`main` → GitHub Release / Tag `v*` → Production-Deployment

Für Produktion:

- Required Reviewer
- Prevent self-review
- nur `main` bzw. Release/Tag
- kein direktes Deployment bei beliebigen Pushes
- Rollback-Konzept verpflichtend

---

# Phase 6 – Release-Prozess

Empfohlene Versionierung:

- `v0.1.0`
- `v0.2.0`
- `v1.0.0`

Ablauf:

1. Entwicklung auf `develop`
2. `release/x.y.z` erstellen
3. QA und Security-Prüfung
4. Merge nach `main`
5. Tag `vx.y.z`
6. GitHub Release veröffentlichen
7. Production-Deployment
8. Rückmerge nach `develop`

---

# Phase 7 – Produktiv-Check

Vor Produktionsbetrieb prüfen:

- README vollständig
- SECURITY.md vorhanden
- CONTRIBUTING.md vorhanden
- CHANGELOG.md vorhanden
- LICENSE geklärt
- Issue Templates vorhanden
- PR Template vorhanden
- CI grün
- Security Checks grün
- Staging getestet
- Rollback dokumentiert
- Backup-Konzept dokumentiert
- Produktions-Secrets gesetzt
- keine `.env` im Repository
- `APP_DEBUG=0`
- HTTPS aktiv
- Mailversand getestet
- Demo-Zugänge entfernt / deaktiviert
- DSGVO-Prozesse getestet
- Audit-Log geprüft
