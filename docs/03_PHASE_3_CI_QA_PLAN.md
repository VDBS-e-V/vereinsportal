# Phase 3 – CI / QA

## Status

Die automatisierte CI-/QA-Basis ist umgesetzt. Jeder Pull Request nach `main` und jeder Push auf `main` wird geprüft; der Workflow kann zusätzlich manuell gestartet werden.

## Workflow

Datei:

`.github/workflows/ci.yml`

## Quality Job

`Quality` läuft mit:

- Checkout ohne persistierte GitHub-Credentials
- PHP 8.5
- Composer 2
- Node.js 22
- MySQL 8.4
- `composer validate --no-check-publish`
- `composer install --prefer-dist --no-interaction --no-progress`
- `npm ci`
- eigene `.env.testing` mit MySQL-Testdatenbank und nicht-persistenten Cache-/Session-/Mail-Treibern
- `php artisan config:clear --env=testing`
- `php artisan migrate:fresh --env=testing --force`
- `php artisan vdbs:library-check`
- `npm run build`
- `git diff --check`
- `php vendor/bin/pest`
- `php vendor/bin/pint --test`

## PHP-Kompatibilität

Ein separater Matrix-Job prüft die unterstützte PHP-Basis:

- PHP 8.4.1
- PHP 8.5

Je Version werden Composer-Manifest, Lockfile-Installation und `composer check-platform-reqs` geprüft.

Die Anwendungspolitik lautet damit: PHP 8.4.1 ist die minimale unterstützte Basis; PHP 8.5 wird ebenfalls kontinuierlich geprüft.

## Statische PHP-Analyse

Der Job `Static Analysis` läuft separat auf PHP 8.5 und führt nach Composer-Installation

`composer analyse`

aus. Dahinter steht Larastan/PHPStan. Diese Analyse ersetzt die frühere, nicht funktionierende PHP-CodeQL-Konfiguration.

## Workflow-Härtung

Aktiv:

- minimale `permissions` (`contents: read`)
- Actions auf vollständige Commit-SHAs gepinnt
- `persist-credentials: false`
- Job-Timeouts
- Concurrency mit Abbruch veralteter Runs

## Required Status Checks

Für `main` sind aktuell verpflichtend:

- `Quality`
- `Static Analysis`
- `Composer Audit`
- `NPM Audit`
- `Dependency Review`
- `Secret Scan`

Ein Pull Request darf erst integriert werden, wenn die verpflichtenden Checks grün und offene Review-Conversations aufgelöst sind.

Die zusätzliche Regel „Branch muss vor Merge auf dem neuesten `main`-Stand sein“ ist aktuell noch nicht aktiviert, gehört inzwischen aber zum verbindlichen Zielzustand des Repository-Gates. Der verbleibende GitHub-Schritt und die Abschlussprobe werden in Issue #13 und `docs/25_GITHUB_SETTINGS_RUNBOOK.md` verfolgt.

## Manuelle QA

Automatisierte CI ersetzt keine Browser-, Accessibility- oder Print-Abnahme. Für Designsystem v1 gilt zusätzlich `docs/09_DESIGN_QA_CHECKLIST.md`; der aktuelle manuelle Abschluss wird in Issue #16 verfolgt. Die reproduzierbare Ausführungsabfolge steht in `docs/26_DESIGN_QA_EXECUTION.md`.
