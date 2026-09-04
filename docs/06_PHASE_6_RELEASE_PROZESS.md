# Phase 6 – Release-Prozess

## Versionierung

Empfohlen: Semantic Versioning

Beispiele:
- `v0.1.0`
- `v0.2.0`
- `v1.0.0`

## Ablauf

1. Features/Fixes nach `develop`
2. `release/x.y.z` aus `develop`
3. QA / Security / Dokumentation
4. Merge nach `main`
5. Tag `vx.y.z`
6. GitHub Release veröffentlichen
7. Production-Deployment
8. Rückmerge nach `develop`

## Release-Branch

Erlaubte Änderungen:
- Bugfixes
- Dokumentation
- Changelog
- Versionsnummern
- Deployment-Vorbereitung

Keine größeren neuen Features.
