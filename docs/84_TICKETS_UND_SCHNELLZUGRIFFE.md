# Tickets und Schnellzugriffe

## Tickets (Modul `app/Modules/Tickets`)

Jede angemeldete Person legt Tickets an (Anfrage oder Störungsmeldung) und schreibt im Ticket mit dem Team. Das Bearbeitungsteam (Berechtigung `tickets.handle`: Administration und Geschäftsstelle) sieht alle Tickets, weist sie zu und bearbeitet sie.

- **Meine Tickets** (`/tickets`, Bereich Konto, Profilmenü): eigene Tickets, „Neues Ticket“, das Ticket mit Verlauf. Die früheren „Meine Tickets“ (Veranstaltungsanmeldungen) heißen jetzt **Meine Anmeldungen** (`/konto/tickets`).
- **Tickets** (`/verwaltung/tickets`, Geschäftsstelle): Warteschlange mit Suche und Filtern (Status, Bearbeitung, Priorität), **Mehrfachauswahl** mit „Mir zuweisen“, „Zuweisung aufheben“ und „Status ändern“.
- **Ticket** (`/tickets/{id}`): Stand, Bearbeitung, **Verlauf und Nachrichten in einer Zeitleiste** (Chat). Das Team ändert Bearbeiter:in, Status und Priorität und kann **interne Notizen** schreiben, die die anfragende Person nicht sieht. Die anfragende Person kann das Ticket schließen; eine neue Nachricht öffnet es wieder.
- **Status:** Offen, In Bearbeitung, Wartet auf Rückmeldung, Gelöst, Geschlossen. Antwortet das Team auf ein offenes Ticket (oder wird es zugewiesen), wird es „In Bearbeitung“; schreibt die anfragende Person bei „Wartet“, „Gelöst“ oder „Geschlossen“, geht es zurück in Bearbeitung bzw. auf Offen.
- **Protokoll:** Anlegen, Nachrichten (ohne Text), Zuweisungen und Statuswechsel stehen im Protokoll des Portals (Kategorie „Tickets“); zusätzlich zeigt jedes Ticket seinen eigenen Verlauf.
- Tabellen `tickets`, `ticket_messages`, `ticket_events` (Migration `2027_01_03_000001_create_tickets.php`). Dienst `Support\TicketService`. Tests: `tests/Feature/Tickets/TicketsTest.php`.
- **E-Mails (ohne Nachrichtentext, nur Link ins Portal):** Neues Ticket an alle Bearbeitenden; Zuweisung an die neue Bearbeiter:in (nicht bei Selbstzuweisung); Schreiben der anfragenden Person an die Bearbeiter:in (ohne Zuweisung an alle Bearbeitenden); Antwort des Teams an die anfragende Person (immer). Interne Notizen lösen nie eine Mail aus. Das Team kann seine Hinweise unter Konto › Benachrichtigungen ausschalten (`tickets_team`). Klasse `Support\TicketNotifier`, Mail `Mail\TicketMail`.
- **Anhänge:** Beim Anlegen und bei jeder Nachricht bis zu fünf Dateien (PDF, Word, Excel, CSV, OpenDocument, ZIP, Text; keine Bilder; Prüfung und Grenzen wie bei den Seminardokumenten). Privat gespeichert (`storage/app/tickets/<id>/`), Abruf nur über das Ticket: anfragende Person und Team; Anhänge interner Notizen nur das Team. Tabelle `ticket_attachments` (Migration `2027_01_04_000001`).
- **Einstieg:** Auf der FAQ-Seite und im Kontaktbereich verweist ein Link angemeldete Personen auf „Ticket erstellen“.
- **Noch nicht enthalten:** Kategorien mit festen Zuständigen, Wiedervorlage, Auswertungen.

## Schnellzugriffe im Profilmenü (Modul `app/Modules/QuickLinks`)

Jede Rolle hat bis zu **drei Links** mit einer **Wichtigkeit** (Zahl, 1 = am wichtigsten). Im Profilmenü erscheinen unter „Wichtige Links“ die wichtigsten Links **aller Rollen** der Person: nach Wichtigkeit sortiert, doppelte Adressen nur einmal, höchstens vier (`QuickLinks::LIMIT`).

- Pflege: Geschäftsstelle › Inhalte › **Schnellzugriffe** (`/verwaltung/schnellzugriffe`, Berechtigung `role_links.manage`). Adressen beginnen mit `/` (Seite im Portal) oder `https://`.
- Die Migration `2027_01_02_000001_create_role_links.php` legt je Rolle drei Ausgangslinks auf vorhandene Seiten an; sie lassen sich im Portal ändern.
- Tests: `tests/Feature/QuickLinks/QuickLinksTest.php`.

## Einspielen

`php artisan migrate` (Migrationen `2027_01_02_000001`, `2027_01_03_000001` und `2027_01_04_000001`), danach `php artisan optimize:clear`; Frontend neu bauen (`npm run build`).
