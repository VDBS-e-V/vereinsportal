# Mitgliedschaften (Modul Membership)

Bereich **Vorstand**, Seite **Mitgliedschaften** (`/vorstand/mitgliedschaften`). Antrag, Aufnahme, Ablehnung und Austritt einer Person,
mit Verlauf und automatischer Steuerung der Rolle **Vereinsmitglied**.

## Wer sieht und pflegt

Berechtigungen `memberships.view` und `memberships.manage` haben **nur Vorstandsmitglieder** (`AccessGovernance/Support/Capabilities.php`).
Geschäftsstelle und Administration sehen Mitgliedschaften nicht. Mitgliedschaftsdaten erscheinen **nicht** in den Personenseiten der
Verwaltung und **nicht** im allgemeinen Protokoll (`docs/42_PROTOKOLL.md`); jede Mitgliedschaft hat stattdessen einen eigenen Verlauf
(`membership_events`). Mitglieder sehen die eigene Mitgliedschaft unter Konto > Profil. Die **öffentlichen Seiten** (Antrag, Registrierung) liegen im Bereich Start.

## Drei Wege zu einer Mitgliedschaft

1. **Vom Vorstand erfasst (ohne digitale Person):** Antrag oder Altbestand wird mit **Dokument** (PDF, JPEG, PNG, bis 10 MB) erfasst:
   Name (Pflicht), optional Geburtsdatum, E-Mail und Beitrittsdatum. Es entsteht **keine** Person und kein Konto.
2. **Online-Antrag (öffentlich, `/mitglied-werden`, Bereich Start):** Das Formular legt **Person, Konto und einen Antrag** an.
   Das Konto ist bis zur Bestätigung der E-Mail-Adresse (signierter Link, 48 Stunden) nicht anmeldbar; danach kann sich die Person
   anmelden, sieht aber nur den Bereich Konto. Die **Rolle Vereinsmitglied** vergibt erst die **Aufnahme durch den Vorstand**. Bei
   Ablehnung bleibt das Konto ohne diese Rolle bestehen.
3. **Registrierungslink:** Der Vorstand sendet einer erfassten Mitgliedschaft mit hinterlegter E-Mail einen Link
   (`/registrierung/{token}`, 14 Tage gültig, einmal verwendbar, nur der Hash wird gespeichert). Das Mitglied legt damit selbst
   Person und Konto an. Der Link beweist den Zugriff auf die Adresse: Das Konto ist **sofort aktiv und verknüpft**, bei laufender
   Mitgliedschaft gilt die Rolle sofort. Ein neuer Link macht den vorherigen ungültig.

### Dubletten und Missbrauch beim Online-Antrag

- Gibt es die E-Mail-Adresse (Konto, Person, erfasste Mitgliedschaft) oder Name mit Geburtsdatum schon, wird **nichts angelegt**.
  Die Webseite antwortet immer gleich („Wir haben Ihnen eine E-Mail gesendet“); an die angegebene Adresse geht eine neutrale
  Nachricht (Anmelden/Passwort zurücksetzen bzw. an den Vorstand wenden). Es gibt keine Auskunft, ob jemand Mitglied ist.
- Schutz: Begrenzung pro Besucher-IP (10 Anträge pro Stunde, Bestätigung erneut senden 5 pro 10 Minuten), verstecktes Köderfeld,
  Pflichtangaben zu Satzung und Datenschutz, Passwortregeln des Portals.
- Im allgemeinen Protokoll steht nur „Konto angelegt (Selbstregistrierung)“, keine Mitgliedschaftsdaten.

## Ablauf und Status

| Status | Bedeutung | Übergänge |
|---|---|---|
| Beantragt | Antrag erfasst (manuell oder online) | aufnehmen (Beitrittsdatum, Notiz) → Aktiv; ablehnen (Grund Pflicht) → Abgelehnt |
| Aktiv | Mitglied | Austritt eintragen, Austritt zurücknehmen, Notiz |
| Beendet | Austrittsdatum liegt zurück | neue Mitgliedschaft möglich |
| Abgelehnt | Antrag abgelehnt | neue Mitgliedschaft möglich |

- Pro Person (Name mit Geburtsdatum bzw. E-Mail) gibt es höchstens **eine offene** (beantragte oder laufende) Mitgliedschaft.
- **Austrittsdatum = letzter Tag der Mitgliedschaft.** Bis einschließlich dieses Tages ist die Person aktiv („Austritt eingetragen“).
  Liegt das Datum in der Vergangenheit, ist die Mitgliedschaft sofort beendet; es darf nicht vor dem Beitritt liegen.
- Der **wirksame Status** (`Membership::effectiveStatus()`) richtet sich nach dem Datum. Der tägliche Befehl `membership:sync`
  (in `routes/console.php` geplant, braucht den Scheduler-Cron `php artisan schedule:run`) schreibt „beendet“ in die Datenbank und
  trägt das Ereignis in den Verlauf ein.

## Dokumente

Im **privaten Speicher** (Disk `local`, `storage/app/private/membership-documents/{id}/`), unter zufälligem Namen; der Originalname steht
nur in der Datenbank (`membership_documents`, mit Prüfsumme). Auslieferung nur über die Mitgliedschaft (Berechtigung `memberships.view`,
Download mit `X-Content-Type-Options: nosniff` und `Cache-Control: private, no-store`). Hochladen und Entfernen: `memberships.manage`;
beides steht im Verlauf. Dieser Speicher gehört in **Backup und Restore** (`docs/29_BACKUP_RESTORE_RUNBOOK.md`) und darf bei Releases nicht überschrieben werden.

## Rolle Vereinsmitglied

`MembershipRoles` hält je aufgenommener Mitgliedschaft eine **automatische** Rollenzuordnung (Quelle: die Mitgliedschaft) vom
Beitrittsdatum bis zum Ende des Austrittstages. Austritt, Rücknahme und Ablehnung ziehen sie nach. Von Hand vergebene Rollen
bleiben unberührt. Hat die Person noch kein Konto, wird die Rolle beim Anlegen des Kontos nachgezogen (`UserAdministration`).

## Datenschutz

Personen mit Mitgliedschaft können nicht gelöscht werden. Dokumente enthalten personenbezogene Daten und liegen privat. Der Verlauf enthält Akteur, Zeitpunkt, Notiz und Daten, aber keine
Zugangsdaten. Aufbewahrungsfristen für Mitgliedschaften legt der Verein fest (Frist und Löschkonzept stehen noch aus).

## Noch offen

- **Zustimmungsnachweise** mit Historie (Satzung, Datenschutz zum Zeitpunkt des Antrags): bisher nur als Pflichtkästchen im Formular.
- Benachrichtigung des Vorstands über neue Online-Anträge.
- Mitgliedsarten (ordentlich, Förder-, Ehrenmitglied), Beiträge und Probezeit: erst nach Klärung der Regeln.
- Aufbewahrungs- und Löschfristen für Dokumente und abgelehnte Anträge.

Tests: `tests/Feature/Membership/MembershipTest.php`. Migration: `php artisan migrate`.
