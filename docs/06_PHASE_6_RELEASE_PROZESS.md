# Phase 6 – Release-Prozess

## Versionierung

Semantic Versioning:

- `v0.1.0`
- `v0.2.0`
- `v1.0.0`

## Normaler Ablauf

1. Änderungen per Pull Request nach `main`.
2. `main` muss alle verpflichtenden CI- und Security-Checks bestehen.
3. `CHANGELOG.md` und Release-relevante Dokumentation prüfen.
4. Tag `vX.Y.Z` auf dem freigegebenen Commit erzeugen.
5. GitHub Release veröffentlichen.
6. Automatisch generierte Release Notes prüfen.
7. Optional freigegebenes Production-Deployment starten.
8. Health Check und Monitoring prüfen.

Ein dauerhafter `develop`- oder zwingender `release/*`-Branch ist nicht erforderlich.

## Release-Branch bei Bedarf

Für längere Stabilisierung kann ausnahmsweise `release/*` verwendet werden. Darauf nur:

- Release-Bugfixes
- Dokumentation
- Changelog
- Versionsnummern
- Deployment-Vorbereitung

Keine neuen größeren Features.

## Hotfix

Dringende Korrektur von aktuellem `main` in `hotfix/*`, vollständige Checks, Pull Request zurück nach `main`, danach neues Patch-Release.
