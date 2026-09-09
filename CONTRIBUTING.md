# Mitwirken

## Grundprinzip

`main` ist der einzige dauerhafte Integrationsbranch. Änderungen werden in kurzen Arbeitsbranches entwickelt und per Pull Request nach `main` integriert.

## Branches

Von aktuellem `main` erstellen:

- `feature/<thema>`
- `fix/<thema>`
- `security/<thema>`
- `refactor/<thema>`
- `docs/<thema>`
- `hotfix/<thema>` für dringende Korrekturen

Keine direkten Feature-Commits auf `main`.

## Ablauf

1. `main` aktualisieren.
2. Einen passenden Arbeitsbranch erstellen.
3. Änderung fokussiert implementieren.
4. Tests und Dokumentation ergänzen.
5. `composer qa` ausführen.
6. Bei Dependency- oder Security-Änderungen zusätzlich `composer security` ausführen.
7. Pull Request nach `main` öffnen.
8. CI- und Security-Checks müssen grün sein.
9. Review-Kommentare auflösen.
10. Per Squash Merge integrieren und Arbeitsbranch löschen.

## Qualitätsregeln

Mindestens relevant:

```text
composer validate --no-check-publish
php vendor/bin/pint --test
php artisan test
php artisan vdbs:library-check
npm run build
git diff --check
```

`composer qa` bündelt die zentralen lokalen Checks.

## Tests

Verhaltensänderungen benötigen passende automatisierte Tests. Sicherheits-, Rollen- und Berechtigungslogik soll nicht nur über Design- oder Stringtests abgesichert werden.

Keine echten personenbezogenen Daten in Test-Fixtures verwenden.

## Pull Requests

Ein Pull Request soll:

- ein klar abgegrenztes Problem lösen,
- den Grund der Änderung erklären,
- relevante Tests nennen,
- Security- und Datenschutzfolgen angeben,
- Migrationen und Rollback-Auswirkungen dokumentieren,
- keine unnötigen Formatierungs- oder Fremdänderungen enthalten.

Große Änderungen nach Möglichkeit in nachvollziehbare, einzeln reviewbare Schritte teilen.

## Designsystem

Neue Designsystem-Bausteine entstehen nur aus realen Produkt- oder Content-Anforderungen. Wiederverwendbare Lösungen anschließend in die Web Content Library übernehmen und `php artisan vdbs:library-check` ausführen.

## Security und Datenschutz

- Keine Secrets oder Produktionszugänge committen.
- Keine realen personenbezogenen Daten in Code, Tests, Issues oder Logs.
- Sicherheitslücken vertraulich gemäß `SECURITY.md` melden.
- Bei Auth-, Session-, Rollen-, Audit- oder Datenschutzänderungen die Auswirkungen im PR explizit beschreiben.

## Commit- und Merge-Historie

Kurze, verständliche Commit-Nachrichten verwenden. Pull Requests werden bevorzugt per Squash Merge integriert. Merge Commits und Rebase Merge sollen im Repository deaktiviert werden.
