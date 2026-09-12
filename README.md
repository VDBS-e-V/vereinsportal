# Vereinsportal

Webanwendung des VDBS e.V. für Vereins-, Portal- und Verwaltungsprozesse auf Basis von Laravel.

## Entwicklungsstatus

Das Repository befindet sich in aktiver Entwicklung. `main` ist der einzige dauerhafte Integrationsbranch und soll jederzeit einen geprüften, grundsätzlich releasefähigen Stand enthalten.

## Technischer Stack

- PHP 8.4.1+ / Laravel 13
- Livewire 4 / Volt
- Tailwind CSS 4
- Vite
- Pest
- MySQL

## Schnellstart

Voraussetzungen: PHP, Composer, Node.js, npm und eine lokale Datenbank.

```text
composer setup
php artisan serve
```

Für die laufende Entwicklung:

```text
composer dev
```

Nach einem Pull mit neuen Datenbankmigrationen muss der lokale Datenbankstand aktualisiert werden:

```text
php artisan migrate
```

Das ist insbesondere vor dem Testen neu hinzugekommener Detailansichten und Fachmodule erforderlich. Lokale Umgebungswerte gehören ausschließlich in `.env` und dürfen nicht committed werden.

## Branch-Modell

Normale Änderungen starten von `main`:

- `feature/*` für neue Funktionen
- `fix/*` für Fehlerbehebungen
- `security/*` für Security-Hardening
- `refactor/*` für technische Überarbeitungen
- `docs/*` für Dokumentation
- `hotfix/*` nur für dringende produktionsnahe Korrekturen

Änderungen gehen per Pull Request zurück nach `main`. Bevorzugte Merge-Methode ist Squash Merge. Direkte Pushes, Force Pushes und Branch-Löschung für `main` sollen über ein GitHub Ruleset verhindert werden.

Details: [CONTRIBUTING.md](CONTRIBUTING.md) und [docs/01_PHASE_1_BRANCHES_UND_RULESETS.md](docs/01_PHASE_1_BRANCHES_UND_RULESETS.md).

## Lokale Qualitätsprüfung

Der zentrale lokale Check ist:

```text
composer qa
```

Zusätzlich:

```text
composer security
```

Die GitHub Actions wiederholen die relevanten Prüfungen auf Pull Requests.

## Web Content Library / Designsystem

Das Designsystem und die Web Content Library werden im Repository mit Tests und Integritätsprüfungen gepflegt. Neue UI-Bausteine entstehen nicht auf Vorrat, sondern aus realen Anforderungen.

## Sicherheit

Sicherheitslücken bitte nicht als öffentliches Issue melden. Der vertrauliche Meldeweg ist in [SECURITY.md](SECURITY.md) beschrieben.

Niemals Secrets, echte personenbezogene Daten, Produktionskonfiguration oder Zugangsdaten in Issues, Pull Requests, Logs oder Test-Fixtures einfügen.

## Mitarbeit

Siehe [CONTRIBUTING.md](CONTRIBUTING.md) und [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Changelog und Releases

Änderungen werden in [CHANGELOG.md](CHANGELOG.md) dokumentiert. Releases folgen Semantic Versioning und werden über Git-Tags `vX.Y.Z` sowie GitHub Releases veröffentlicht.

## Lizenz

Siehe `LICENSE`.
