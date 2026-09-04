# Vereinsportal

Webanwendung für den Vereinsbetrieb mit Laravel.

## Überblick

Das Projekt stellt eine zentrale Plattform für Vereinsprozesse bereit, inklusive Verwaltung, Kennzahlen, Benutzer- und Berechtigungslogik sowie Abläufe rund um Organisation und Administration.

## Status

- Repository-Setup und GitHub-Standards in Vorbereitung
- Branch- und Ruleset-Strategie dokumentiert
- CI- und Sicherheits-Workflows ergänzt
- Produktionsreife und Deployment-Workflow noch finalisieren

## Schnellstart

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

## Branching und Qualitätsrichtlinien

- `main`: stabiler produktionsnaher Branch
- `develop`: Integrationsbranch für aktive Entwicklung
- `feature/*`, `fix/*`, `security/*`: normale Arbeitspfade
- `release/*`, `hotfix/*`: Release- und Notfallprozesse

Für Details siehe:

- [docs/00_GESAMTPLAN.md](docs/00_GESAMTPLAN.md)
- [docs/01_PHASE_1_BRANCHES_UND_RULESETS.md](docs/01_PHASE_1_BRANCHES_UND_RULESETS.md)

## Qualitäts- und Sicherheitschecks

Vor einem Merge bzw. einer Freigabe gelten:

- `composer validate`
- `php artisan test`
- `php vendor/bin/pint --test`
- Sicherheits- und Abhängigkeits-Checks aus dem GitHub-Workflow

## Mitwirken

Bitte siehe [CONTRIBUTING.md](CONTRIBUTING.md).

## Sicherheit

Bitte Sicherheitslücken nicht öffentlich im Issue-Tracker melden. Details stehen in [SECURITY.md](SECURITY.md).

## Changelog

Die Änderungen werden in [CHANGELOG.md](CHANGELOG.md) dokumentiert.

## Lizenz

Dieses Projekt wird entsprechend der gewählten Repository-Lizenz verteilt. Bitte die Lizenz-Datei und die Projektvereinbarungen im Repository prüfen.
