# Stabile Version: Nutzerverwaltung und Identity Service

Der Branch `release/identity-users` enthält nur die Teile des Portals, die für Konten, Rollen und die Anmeldung anderer Systeme nötig sind. Er geht vom Stand `claude/cool-ramanujan-fgmbcu` (Commit 01bf92d) aus. Alles andere ist entfernt und kehrt zurück, indem man es aus diesem Entwicklungsbranch übernimmt.

## Enthalten

| Modul | Aufgabe |
|---|---|
| Access | Anmeldung, Passwort-Reset, Zwei-Faktor, Konto-Seiten (Profil, E-Mail, Sitzungen, Löschung), Benachrichtigungen |
| AccessGovernance | Nutzerverwaltung, Rollen, Berechtigungen, Einladungen |
| AccessRequests | Anträge auf Zugang zum Portal (öffentliches Formular, Prüfung durch die Geschäftsstelle) |
| People | Personenverwaltung |
| Audit | Protokoll der Änderungen an Konten und Rollen |
| Sso | Identity Service (OpenID Connect): Anbindungen, Schlüssel, Zustimmung, Abmeldung |
| MailBuilder | Mail-Grundlage: Texte der Systemmails (Konto, Zugangsanträge), Mail-Design, Bilder |
| Setup, System | Ersteinrichtung und Betrieb (Zustand, Aufgaben, Protokolle, Warteschlange) |
| Pages | Rechtstexte (Impressum, Datenschutz, Barrierefreiheit) |
| NavigationControl, QuickLinks | Bereichsmenü und Schnellzugriffe im Profilmenü |
| DesignLibrary | Designsystem und seine Prüfung (`design:check`) |

Portal-Gerüst: Startseite, „Über das Portal“, „Zugang zum Portal“, Bereiche Konto, Geschäftsstelle (Nutzer, Zugangsanträge, Personen, Seiten, Schnellzugriffe, Systemmails) und Admins (Protokoll, System, Anbindungen, Web-Control, Design-Bibliothek).

## Entfernt

Seminare, Kostenvoranschläge, Koordinationen, Schulen (Partner), Mitgliedschaften, Einarbeitung, Vorstand (Sitzungen), Aufgaben, Tickets, Anträge (Forms), Rundschreiben, Teamkontakte, Artikel, FAQ und Kontaktformular. Die Bereiche Vorstand, Mitglieder, Teamende, Mitarbeitende, Schulen, Koordination und Bildungsreferenten bleiben als Rollen und Bereichsnamen bestehen, zeigen aber keine Seiten und erscheinen deshalb nicht im Menü.

## Was sich geändert hat

- **Migrationen:** nur Tabellen der enthaltenen Module. `mail_templates` und `mail_template_versions` stehen konsolidiert in einer Migration (mit den Spalten des Mail-Builders). Eine bestehende Entwicklungsdatenbank bitte mit `php artisan migrate:fresh --seed` neu aufbauen; Datenbanken des Entwicklungsbranches enthalten noch Tabellen der entfernten Module, die hier ohne Wirkung sind.
- **Berechtigungen und Rollen:** unverändert, auch die der entfernten Funktionen. So bleiben Konten und Rollenzuweisungen beim Zurückholen von Modulen gültig.
- **Benachrichtigungen:** nur noch „Neue Zugangsanträge“.
- **Geplante Läufe:** `access-requests:prune`, `passport:purge`, `account:purge` und der Herzschlag des Planers.
- **Composer-Pakete** für entfernte Funktionen (`setasign/fpdi`, `setasign/fpdf`) bleiben in `composer.json`, damit `composer.lock` unverändert und gültig bleibt.

## Prüfen

`php artisan migrate:fresh --seed`, `npm run build`, `php vendor/bin/pest`, `php artisan design:check`. Die CI führt zusätzlich Pint und PHPStan aus.
