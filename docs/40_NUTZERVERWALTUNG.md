# Nutzerverwaltung (Modul Access-Governance)

Unter `/verwaltung/nutzer`, sichtbar im Header als „Verwaltung“ für alle mit der Berechtigung `users.view`.

## Berechtigungen

Eine Berechtigung gilt, wenn der Nutzer eine **zurzeit gültige** Rolle (Beginn erreicht, Ende nicht überschritten) hat, die sie
enthält. Die Zuordnung steht an einer Stelle: `app/Modules/AccessGovernance/Support/Capabilities.php`.

| Berechtigung | Bedeutung | Rollen |
|---|---|---|
| `users.view` | Liste und Details ansehen | Administration, Geschäftsstelle, Vorstand |
| `users.manage` | anlegen, einladen, sperren, Zwei-Faktor zurücksetzen, Passwort-Link senden | Administration, Geschäftsstelle, Vorstand |
| `roles.assign` | Rollen vergeben und beenden | Administration, Geschäftsstelle, Vorstand; die Rolle **Administration** vergibt nur die Administration |

Die Berechtigungen sind als Gates registriert (`can:users.view` usw.), `AccessGovernanceServiceProvider`.

## Seiten

- **Liste:** Kennzahlen (aktiv, eingeladen, ohne Zwei-Faktor, gesperrt), Werkzeugleiste mit Suche (Name, E-Mail), Filter Status und
  Rolle, aktive Filter als Tags, sortierbare Tabelle, Paginierung (15 pro Seite, Filter bleiben erhalten).
- **Details:** Konto, Person, Zwei-Faktor-Stand, gültige Rollen mit Beenden, Rolle vergeben, frühere Rollen als Verlauf.
- **Anlegen:** Person und Konto in einem Schritt (Status „eingeladen“, Zufallspasswort), optional mit Startrolle.

## Einladung

Die Einladung ist ein Link zum Festlegen des Passworts, **sieben Tage** gültig (eigener Passwort-Broker `invitations` in
`config/auth.php`; Links zum Zurücksetzen gelten 60 Minuten). Wer sein erstes Passwort setzt, ist danach aktiv und hat die
Adresse bestätigt. Erneut senden ist möglich, solange das Konto noch „eingeladen“ ist.

## Schutz vor Aussperrung und Fehlern

- Das eigene Konto kann man nicht sperren.
- Die **letzte aktive Administration** kann nicht gesperrt und ihre Rolle nicht beendet werden.
- Rollen werden nie gelöscht, sondern mit einem Enddatum beendet (Nachvollziehbarkeit); Vergabe speichert, wer sie erteilt hat.
- Sperren erhöht die Sitzungsversion und beendet damit sofort alle Sitzungen des Kontos.
- Zwei-Faktor zurücksetzen erfordert eine Bestätigung im Dialog; wählen Sie das nur, wenn die Identität sicher feststeht.

## Tests

`tests/Feature/Administration/UserAdministrationTest.php`.

## Offen

- Das Protokoll (wer hat wann was geändert) wartet auf das Audit-Modul; bis dahin schreibt `UserAdministration` Einträge ins
  Anwendungs-Log (`admin.user.*`).
- Personen ohne Konto, Mitgliedschaften und das Bearbeiten der Stammdaten durch die Geschäftsstelle sind eigene Module.
- Sammelaktionen (mehrere Konten auswählen) und CSV-Export fehlen.
