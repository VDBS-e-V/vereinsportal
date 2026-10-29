# Personenverwaltung (Modul People)

Unter `/verwaltung/personen`. Personen sind Stammdaten und unabhängig vom Login: eine Person kann ohne Benutzerkonto existieren.
Der Umschalter „Nutzer | Personen“ im Seitenkopf (`<x-admin.nav>`) erscheint, wenn beide Bereiche sichtbar sind.

## Berechtigungen

| Berechtigung | Bedeutung | Rollen |
|---|---|---|
| `people.view` | Liste und Details ansehen | Administration, Geschäftsstelle, Vorstand |
| `people.manage` | anlegen, bearbeiten, löschen | Administration, Geschäftsstelle, Vorstand |

„Konto aus Person anlegen“ braucht zusätzlich `users.manage`. Zuordnung: `AccessGovernance/Support/Capabilities.php`.

## Seiten

- **Liste:** Kennzahlen, Suche (Name, E-Mail, Ort), Filter Konto (mit/ohne), Sortierung nach Name, E-Mail, Ort, Geburtsdatum, Paginierung.
- **Details:** Stammdaten, Kontostatus mit Sprung zur Nutzerverwaltung, „Konto anlegen und einladen“ (Dialog).
- **Anlegen/Bearbeiten:** Name, Geburtsdatum, Kontakt, Anschrift; Land als zweistelliger Code.

## Regeln

- Die E-Mail-Adresse einer Person **mit Konto** ist die Anmeldung und nur über „Konto > E-Mail“ änderbar; das Formular zeigt sie gesperrt.
- Personen **mit Konto** werden nicht gelöscht, das Konto wird stattdessen gesperrt.
- E-Mail-Adressen sind in Personen und Konten eindeutig; Eingaben werden getrimmt und die Adresse klein geschrieben.
- Ein Konto aus einer Person hat den Status „eingeladen“ und die Einladung gilt sieben Tage (siehe `docs/40_NUTZERVERWALTUNG.md`).

Tests: `tests/Feature/Administration/PersonAdministrationTest.php`.
