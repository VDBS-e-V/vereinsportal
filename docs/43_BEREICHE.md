# Bereiche und Kopfbereich

Das Portal ist in **Bereiche** gegliedert (`App\Support\PortalAreas`: Start, Konto, Vorstand, Mitglieder, Teamende, Schulen,
Koordination, Geschäftsstelle, Bildungsreferenten, Admins). **Jede Seite gehört zu genau einem Bereich.** Der Katalog steht an einer
Stelle: `app/Support/PortalNavigation.php` (`catalog()`).

## Kopfbereich

- **Zeile 1:** die Bereiche, die der Nutzer sehen darf, dahinter die Systeme (`config/portal.php`, nur mit gesetzter Adresse).
  Der aktuelle Bereich ist hervorgehoben (`aria-current="true"`). Ein Klick führt zur ersten Seite des Bereichs, die der Nutzer sehen darf.
- **Zeile 2:** Name des aktuellen Bereichs und dessen Seiten (`aria-current="page"` auf der aktuellen). Rechts das Avatar-Menü.
- **Start** ist **öffentlich**: Seiten Über das Portal (`/ueber-das-portal`), Zugang zum Portal (`/zugang-zum-portal`), FAQ
  (`/ueber-das-portal#portal-faq`, Anker, nie „aktuell“) und Kontakt (`/kontakt`). Sie stehen in Zeile 2 für Gäste und Angemeldete,
  auch auf der Anmeldeseite und auf Fehlerseiten (dort ohne Sitzung). Die Struktur entspricht der bisherigen Hauptnavigation
  (`ul` > `li` > `a`, optional ein verschachteltes Untermenü): Einträge im Katalog können `children` haben, das Untermenü erscheint ab 1100 px
  beim Überfahren oder Fokussieren, auf kleineren Bildschirmen eingerückt unter dem Eintrag. Erreichbar über das Logo. **Konto:** über das Avatar-Menü (Mein Konto, Sicherheit); es ist ein eigener Bereich, dessen Seiten
  Profil, E-Mail, Passwort, Sicherheit und Sitzungen in Zeile 2 stehen. Beide stehen nicht in Zeile 1.
- Gäste sehen die öffentliche Navigation; auf Fehlerseiten (`safe`) liest der Kopf weder Sitzung noch Datenbank.

## Regel: Bereichswechsel nur über Zeile 1

Man wechselt den Bereich nur durch Klick auf einen Bereich in Zeile 1. **Links aus einem Bereich in einen anderen** (z. B. von einer
Nutzerseite zur Personenseite eines anderen Bereichs) sind Ausnahmen: nur nach Abstimmung, mit guter Begründung, und die Begründung
steht dann im Code direkt am Link.

## Wer sieht welchen Bereich

Ein Bereich erscheint, wenn der Nutzer **eine Rolle des Bereichs** hat (Vorstand sieht alle) **und mindestens eine Seite darin sehen
darf**. Bereiche ohne Seiten erscheinen nicht. Jede Seite bleibt zusätzlich durch ihre Berechtigung (`can:…`) geschützt; der Katalog
bestimmt nur die Darstellung, er ersetzt keine Berechtigungsprüfung. Ein Bereich ohne passende Rolle erscheint nicht, auch nicht ausgegraut.

| Bereich | Rollen (zusätzlich Vorstandsmitglied) | Seiten heute |
|---|---|---|
| Vorstand | Vorstandsmitglied | Mitgliedschaften |
| Mitglieder | Vereinsmitglied | – |
| Teamende | Teamende | – |
| Schulen | – | – |
| Koordination | Koordination | – |
| Geschäftsstelle | Geschäftsstelle, Administration | Nutzer, Personen |
| Bildungsreferenten | Bildungsreferenten | – |
| Admins | Administration | Protokoll (`audit.view`), Design-Bibliothek (`design.view`; Administration und Vorstand) |
| Konto | alle Angemeldeten | Profil, E-Mail, Passwort, Sicherheit, Sitzungen |
| Start | öffentlich | Über das Portal, Zugang zum Portal, FAQ, Kontakt |

## Neue Seite oder neuer Bereich

1. **Jede neue Seite** in `PortalNavigation::catalog()` unter dem richtigen Bereich eintragen: `label`, `route`, `match` (Routenmuster
   inklusive Unterseiten, z. B. `admin.users.*`; `null` für Anker auf andere Seiten), `can` (Berechtigung; ohne `can` gilt die Seite
   als öffentlich bzw. für alle Angemeldeten), optional `fragment` (Anker) und `children` (Untermenü). Der Test `assigns every page behind the login to an area`
   schlägt fehl, solange eine angemeldete Seite keinem Bereich zugeordnet ist.
2. Der Test `makes every page reachable…` prüft, dass jede Rolle mit der Berechtigung der Seite den Bereich auch sieht.
3. Neue Rollen für einen Bereich: im Katalog bei `roles` eintragen.

Tests: `tests/Feature/Navigation/PortalNavigationTest.php`.
