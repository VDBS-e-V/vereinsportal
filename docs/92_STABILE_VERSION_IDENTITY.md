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

## Bestehende Konten übernehmen (Test- oder Live-Seite)

Konten, Personen, Rollen, Zwei-Faktor-Einstellungen, Anbindungen (SSO) und Protokoll liegen in Tabellen, die die stabile Version unverändert weiterverwendet. Sie gehen **nicht** verloren, solange die Datenbank nicht neu aufgebaut wird:

1. **Sicherung:** Datenbank-Dump (und den Ordner `storage/app`) ziehen, bevor etwas geändert wird.
2. Neue Version hochladen (Dateien ersetzen, `composer install --no-dev`, Assets bauen) wie bei jedem Update.
3. **Nur** `php artisan migrate --force` ausführen, **nicht** `migrate:fresh`. Die Migrationen der stabilen Version tragen dieselben Namen wie bisher; was schon gelaufen ist, wird übersprungen. Neu laufen nur `2026_11_23_000001_create_mail_templates_tables` (ergänzt fehlende Spalten, ohne Vorhandenes anzutasten) und `2027_02_01_000001_create_mail_builder_tables` (Mail-Design und Bilder).
4. `php artisan config:clear` und `php artisan storage:link` (Bilder für Mails).
5. Anmelden und prüfen: beide Konten melden sich mit ihrem Passwort an, Rollen und Zwei-Faktor stimmen, unter Admins › Anbindungen sind die Anwendungen noch da.

Tabellen der entfernten Module (Seminare, Mitgliedschaften, Aufgaben …) bleiben in der Datenbank liegen, sind aber ohne Wirkung. Sie lassen sich später mit einer eigenen Aufräum-Migration entfernen, sobald klar ist, dass die Daten nicht mehr gebraucht werden. Hängt an einem Konto noch eine Mitgliedschaft, ein Schulkonto oder Ähnliches, bleibt das Konto selbst unberührt.

## Prüfen

`php artisan migrate:fresh --seed`, `npm run build`, `php vendor/bin/pest`, `php artisan design:check`. Die CI führt zusätzlich Pint und PHPStan aus.
