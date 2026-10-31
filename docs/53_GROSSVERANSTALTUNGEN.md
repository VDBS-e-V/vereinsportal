# 53 – Großveranstaltungen (Anmeldesystem)

Stufe 4 aus `docs/47_SEMINARPLANUNG.md`, Abschnitt 8: Veranstaltungen für Schülerinnen und Schüler (SuS) mehrerer Schulen. Das hat **nichts mit Mitgliedschaft** zu tun.
Modul `app/Modules/Events`.

## Seiten

| Bereich | Seite | Wer |
|---|---|---|
| Start (öffentlich) | `/veranstaltungen` Liste, `/veranstaltungen/{kennung}` Anmeldung, `/veranstaltungen/teilnahmebestaetigung` | alle (Entwürfe sieht nur die Koordination) |
| Koordination | `/koordination/veranstaltungen` Liste, Veranstaltung, Termine, Anmeldungen, Import, Export, Anwesenheit | `events.view` (ansehen), `events.manage` (ändern); Koordination, Geschäftsstelle, Vorstand |

## Ablauf

1. **Veranstaltung** anlegen: Titel, Beschreibung, Ort, Plätze (leer = unbegrenzt), Anmeldeschluss, Status (Entwurf, Anmeldung offen, Anmeldung geschlossen, Durchgeführt) und **Termine** (Tage). Anmeldung geht nur bei „Anmeldung offen“, vor dem Schluss und mit freiem Platz.
2. **Selbstanmeldung** (ohne Konto, Anrede „du“): Name, Schule, Klasse, Geburtsdatum, E-Mail und Einwilligung. Absenden schickt einen **Bestätigungslink** (48 Stunden, signiert). Erst die Bestätigung macht die Anmeldung gültig und löst die **automatische Anmeldebestätigung** mit **Ticketcode** (8 Zeichen, z. B. K7QM-4XWD) und Terminen aus. Bei bekannten Anmeldungen (gleiche Adresse und Name) ist die Antwort an den Besucher dieselbe; es geht der Link bzw. die Bestätigung erneut raus. Schutz: Formularschutz (`docs/51_FORMULARSCHUTZ.md`) und Drosselung. Unbestätigte Anmeldungen zählen 48 Stunden als belegter Platz.
3. **Liste hochladen** im Auftrag von Lehrkräften (CSV: Name, Schule, Klasse, Geburtsdatum, E-Mail; alles oder nichts, Fehler je Zeile, auch wenn die Plätze nicht reichen). Die Anmeldungen gelten sofort als bestätigt; optional gehen die Anmeldebestätigungen an die angegebenen Adressen.
4. **Anwesenheit** je Termin: Liste mit Suche und Knopf je Person sowie Eingabe des **Ticketcodes**.
5. **Teilnahmebestätigung:** SuS rufen sie unter `/veranstaltungen/teilnahmebestaetigung` mit Ticketcode **und** E-Mail-Adresse ab, sobald mindestens eine Anwesenheit eingetragen ist. Die druckbare Seite nennt Name, Klasse, Schule, Veranstaltung und die besuchten Termine (kein Geburtsdatum). Die Koordination kann sie je Anmeldung ebenfalls öffnen.
6. **Export:** Anmeldungen mit Ticketcode und Anwesenheit je Termin als CSV (Formelzeichen geschützt).

## Verantwortung und Datenschutz

- Für die Erlaubnis zur Teilnahme sind die SuS selbst verantwortlich; das steht in der Anmeldeseite und in der Mail.
- **Aufbewahrung:** Anmeldungen werden **drei Jahre nach dem letzten Termin** gelöscht (`portal.events.retention_years`), unbestätigte nach 48 Stunden. Befehl `php artisan events:prune` (täglich geplant, `--dry-run` zählt nur).
- Das allgemeine Protokoll nennt nur Veranstaltungstitel, Feldnamen und Anzahlen (`event.created/updated/deleted/registrations_imported/registration_removed`), nie Namen, Schulen, Geburtsdaten oder Adressen.
- Veranstaltungen mit Anmeldungen lassen sich nicht löschen (stattdessen schließen).
- Noch nicht gebaut: Platztausch zwischen Veranstaltungen, Serienmails an alle Angemeldeten.

## Warteliste, Abmeldung und Erinnerung

- **Warteliste:** Ist eine Veranstaltung ausgebucht und die Warteliste eingeschaltet (Einstellung an der Veranstaltung, Standard an), melden sich weitere SuS trotzdem an. Sie bestätigen ihre E-Mail-Adresse wie alle anderen, bekommen aber keinen Ticketcode, sondern die Nachricht „Du stehst auf der Warteliste“. Wartende zählen nicht als belegte Plätze, nehmen nicht an der Anwesenheitsliste teil und bekommen keine Teilnahmebestätigung. Ist die Warteliste aus, bleibt es bei der Meldung „ausgebucht“.
- **Nachrücken:** Wird ein Platz frei (Abmeldung, Entfernen durch die Koordination, mehr Plätze, Bestätigung nach Freigabe), rücken Wartende in der Reihenfolge ihrer Bestätigung automatisch nach und erhalten die Anmeldebestätigung mit Ticketcode („Ein Platz ist frei geworden“).
- **Abmeldung durch die SuS:** Jede Anmeldebestätigung, Wartelisten- und Erinnerungsmail enthält einen signierten Abmelde-Link. Er öffnet eine Seite mit Bestätigung (der Abruf allein löscht nichts); danach sind die Angaben gelöscht und der Platz ist frei.
- **Erinnerung:** `php artisan events:remind` (täglich geplant, `--dry-run` zählt nur) erinnert bestätigte Angemeldete einmalig, wenn der erste anstehende Termin innerhalb von 36 Stunden liegt (Ticketcode, Termine, Abmelde-Link).
- Die CSV-Liste enthält die Spalte „Warteliste“.
