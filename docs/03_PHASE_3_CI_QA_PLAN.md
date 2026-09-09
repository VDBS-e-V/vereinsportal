# Phase 3 – CI / QA

## Ziel

Jeder Pull Request nach `main` und jeder Push auf `main` wird automatisiert geprüft.

## Workflow

Datei:

`.github/workflows/ci.yml`

## Quality Job

- Checkout ohne persistierte GitHub-Credentials
- PHP 8.5
- Composer 2
- Node.js 22
- MySQL 8.4
- `composer validate --no-check-publish`
- `composer install`
- `npm ci`
- `php artisan migrate:fresh --force`
- Pest
- Pint
- `php artisan vdbs:library-check`
- `npm run build`
- `git diff --check`

## PHP-Kompatibilität

Ein separater Matrix-Job prüft, ob der Composer-Lockfile weiterhin mit den in `composer.json` unterstützten PHP-Versionen installierbar ist.

## Workflow-Härtung

- minimale `permissions`
- Actions auf Commit-SHAs gepinnt
- `persist-credentials: false`
- Job-Timeouts
- Concurrency mit Abbruch veralteter Runs

## Required Status Check

Für `main` mindestens den Check `Quality` verpflichtend machen. Weitere stabile Security-Checks können zusätzlich in das Ruleset aufgenommen werden.

Ein Pull Request darf erst integriert werden, wenn die verpflichtenden Checks grün und offene Review-Conversations aufgelöst sind.
