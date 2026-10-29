# Mitgliedschaften (Modul Membership)

Bereich **Vorstand**, Seite **Mitgliedschaften** (`/vorstand/mitgliedschaften`). Antrag, Aufnahme, Ablehnung und Austritt einer Person,
mit Verlauf und automatischer Steuerung der Rolle **Vereinsmitglied**.

## Wer sieht und pflegt

Berechtigungen `memberships.view` und `memberships.manage` haben **nur Vorstandsmitglieder** (`AccessGovernance/Support/Capabilities.php`).
Geschäftsstelle und Administration sehen Mitgliedschaften nicht. Mitgliedschaftsdaten erscheinen **nicht** in den Personenseiten der
Verwaltung und **nicht** im allgemeinen Protokoll (`docs/42_PROTOKOLL.md`); jede Mitgliedschaft hat stattdessen einen eigenen Verlauf
(`membership_events`). Mitglieder sehen die eigene Mitgliedschaft unter Konto > Profil.

## Ablauf und Status

| Status | Bedeutung | Übergänge |
|---|---|---|
| Beantragt | Antrag erfasst | aufnehmen (Beitrittsdatum, Notiz) → Aktiv; ablehnen (Grund Pflicht) → Abgelehnt |
| Aktiv | Mitglied | Austritt eintragen, Austritt zurücknehmen, Notiz |
| Beendet | Austrittsdatum liegt zurück | neue Mitgliedschaft möglich |
| Abgelehnt | Antrag abgelehnt | neue Mitgliedschaft möglich |

- Ohne Antrag kann der Vorstand eine Person **direkt aufnehmen** (z. B. Altbestand).
- Pro Person gibt es höchstens **eine offene** (beantragte oder laufende) Mitgliedschaft.
- **Austrittsdatum = letzter Tag der Mitgliedschaft.** Bis einschließlich dieses Tages ist die Person aktiv („Austritt eingetragen“).
  Liegt das Datum in der Vergangenheit, ist die Mitgliedschaft sofort beendet; es darf nicht vor dem Beitritt liegen.
- Der **wirksame Status** (`Membership::effectiveStatus()`) richtet sich nach dem Datum. Der tägliche Befehl `membership:sync`
  (in `routes/console.php` geplant, braucht den Scheduler-Cron `php artisan schedule:run`) schreibt „beendet“ in die Datenbank und
  trägt das Ereignis in den Verlauf ein.

## Rolle Vereinsmitglied

`MembershipRoles` hält je aufgenommener Mitgliedschaft eine **automatische** Rollenzuordnung (Quelle: die Mitgliedschaft) vom
Beitrittsdatum bis zum Ende des Austrittstages. Austritt, Rücknahme und Ablehnung ziehen sie nach. Von Hand vergebene Rollen
bleiben unberührt. Hat die Person noch kein Konto, wird die Rolle beim Anlegen des Kontos nachgezogen (`UserAdministration`).

## Datenschutz

Personen mit Mitgliedschaft können nicht gelöscht werden. Der Verlauf enthält Akteur, Zeitpunkt, Notiz und Daten, aber keine
Zugangsdaten. Aufbewahrungsfristen für Mitgliedschaften legt der Verein fest (Frist und Löschkonzept stehen noch aus).

## Noch offen

- **Dokumente** (Beitrittserklärung) und **Zustimmungsnachweise** mit Historie im privaten Speicher: eigener Folgepatch.
- Mitgliedsarten (ordentlich, Förder-, Ehrenmitglied), Beiträge und Probezeit: erst nach Klärung der Regeln.
- Self-Service-Antrag über das Portal und Benachrichtigungen per E-Mail.

Tests: `tests/Feature/Membership/MembershipTest.php`. Migration: `php artisan migrate`.
