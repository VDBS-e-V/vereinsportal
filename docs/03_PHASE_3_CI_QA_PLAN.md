# Phase 3 – CI / QA Planung

## Ziel

Jeder relevante Pull Request und Push soll automatisch geprüft werden.

## Geplanter Workflow

Datei:

`.github/workflows/ci.yml`

Trigger:

- Pull Requests nach `develop`
- Pull Requests nach `main`
- Pull Requests nach `release/*`
- Push auf `develop`
- Push auf `main`

## Geplante Checks

1. Checkout
2. PHP einrichten
3. `composer validate`
4. `composer install`
5. PHPUnit
6. `php tools/qa/run_all.php`

## Required Status Checks

Nach dem ersten erfolgreichen Workflow-Lauf in den Rulesets aktivieren:

- `ci / qa`

Optional später:
- Lint
- statische Analyse
- zusätzliche Integrationstests

## Ziel

Ein PR darf erst gemerged werden, wenn:
- CI erfolgreich
- Review erfolgreich
- Conversations resolved
