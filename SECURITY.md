# Security Policy

## Unterstützter Stand

Sicherheitskorrekturen beziehen sich auf den aktuellen `main`-Branch und auf veröffentlichte Versionen, die ausdrücklich als unterstützt gekennzeichnet sind.

## Sicherheitslücke melden

Sicherheitslücken niemals öffentlich als Issue, Discussion oder Pull Request veröffentlichen.

Bevorzugter Weg:

1. Repository auf GitHub öffnen.
2. `Security` wählen.
3. `Report a vulnerability` beziehungsweise eine private Security Advisory erstellen.

Falls innerhalb der Organisation ein separater vertraulicher Maintainer-Kanal eingerichtet ist, kann dieser ebenfalls verwendet werden.

Eine Meldung sollte enthalten:

- betroffenen Bereich,
- nachvollziehbare Reproduktionsschritte,
- mögliche Auswirkungen,
- betroffene Version oder Commit,
- mögliche Gegenmaßnahme, falls bekannt.

Keine unnötigen echten personenbezogenen Daten oder produktiven Secrets mitsenden.

## Umgang mit Meldungen

Security-Fixes werden priorisiert und können in einem `security/*`- oder `hotfix/*`-Branch vorbereitet werden. Vertrauliche Details bleiben bis zur koordinierten Behebung nicht öffentlich.

## Repository-Sicherheitsregeln

- `.env`, private Schlüssel und Zugangsdaten bleiben außerhalb von Git.
- Produktions-Secrets werden nur über GitHub Environments oder den Secret Store des Hostings verwaltet.
- GitHub Actions erhalten minimale `GITHUB_TOKEN`-Berechtigungen.
- Fremde Actions werden auf vollständige Commit-SHAs gepinnt.
- `pull_request_target` wird für untrusted Code vermieden.
- Dependency-, CodeQL- und Secret-Checks laufen automatisiert.
- Öffentliche Issues und Test-Fixtures enthalten keine echten personenbezogenen Daten.

## GitHub-Einstellungen

Zusätzlich zu den versionierten Dateien sollen für das Repository aktiviert sein:

- Dependabot Alerts
- Dependabot Security Updates
- Secret Scanning
- Push Protection
- Code Scanning
- Private Vulnerability Reporting
