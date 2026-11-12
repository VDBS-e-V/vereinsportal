# Aufgabensystem (Projekte, Status, Kanban)

Ein allgemeines Aufgabensystem für das ganze Portal. Eigener Bereich **Aufgaben** in der Kopfleiste (Modul `Tasks`); sichtbar für Vorstand, Administration, Geschäftsstelle, Koordination, Bildungsreferenten, Geschäftsführung, Kassenführung und Mitarbeitende (Berechtigung `tasks.use`). Teamende, Mitglieder und Schulen sehen den Bereich nicht.

## Projekte und Zugriff

- Aufgaben liegen in **Projekten**. Ein Projekt sehen nur seine Mitglieder.
- **Mitglieder** (ausdrücklich eingetragen) legen Aufgaben an, bearbeiten, verschieben und kommentieren sie.
- **Verwaltende** ändern zusätzlich Einstellungen, Mitglieder und Spalten des Projekts, löschen fremde Aufgaben und Kommentare und archivieren das Projekt.
- Optional macht eine **Rolle** alle ihre Inhaber automatisch zu Mitgliedern und Verwaltenden (Projekt „Vorstand“: Rolle Vorstand).
- **Projekte anlegen und löschen** darf nur, wer `tasks.admin` hat (Administration und Vorstand). Diese Rollen sehen auch alle Projekte.
- Das Projekt **Vorstand** entsteht von selbst, ist an die Rolle Vorstand gebunden und lässt sich weder archivieren noch löschen. Die Aufgaben aus den Vorstandssitzungen und Beschlüssen liegen dort; die frühere Aufgabenliste des Vorstands (Tabelle `board_tasks`) wurde beim Einspielen übernommen (erledigte in „Erledigt“, offene in „Offen“).

## Status (Spalten)

- Jedes Projekt hat eigene Spalten, zunächst *Offen, In Arbeit, Wartet, Erledigt*. Unter *Einstellungen* lassen sie sich umbenennen, ordnen, ergänzen und löschen. Eine Spalte kann als **„gilt als erledigt“** gekennzeichnet sein; mindestens eine muss es sein.
- Eine Aufgabe in einer Erledigt-Spalte ist erledigt (Zeitstempel). Beim Löschen einer Spalte ziehen ihre Aufgaben in die erste verbleibende um.

## Ansichten

- **Kanban:** Spalten nebeneinander, Karten per Ziehen und Ablegen verschieben (JavaScript `public/vendor/kanban/kanban.js`). Ohne JavaScript oder per Tastatur: „Verschieben“ an jeder Karte. In jeder Spalte lässt sich schnell eine Aufgabe hinzufügen.
- **Liste:** Tabelle mit Sammelaktionen für die Auswahl: erledigt, wieder öffnen, in Spalte verschieben, zuweisen (Mehrfachauswahl), löschen (nur Verwaltende).
- **Filter** in beiden Ansichten: Suche, Zuständige (ich, niemand, Person), Priorität, nur Überfällige, in der Liste offene/erledigte/alle.
- **Meine Aufgaben:** alle Ihnen zugewiesenen Aufgaben über alle Projekte.

## Aufgabe

Titel, Beschreibung, Priorität (niedrig, normal, hoch), Fälligkeit, mehrere Zuständige (nur Mitglieder des Projekts), Spalte, Kommentare. Im Projekt Vorstand zusätzlich Bezug zu Sitzung und Beschluss; die Sitzungsseite legt Aufgaben dort an und zeigt sie.

## Datenschutz

Name der Zuständigen und Kommentare werden gespeichert; das Portalprotokoll (Gruppe „Aufgaben“) nennt Titel und Projekt, nie Beschreibung oder Kommentar. Es gibt keine automatische Löschung; Aufgaben und Projekte löscht die Verwaltung. Die Datenschutzerklärung enthält den Abschnitt „Aufgabenverwaltung“. Bereits gespeicherte Datenschutzseiten müssen ihn von Hand erhalten.

## Technik

- Tabellen `task_projects`, `task_project_members`, `task_statuses`, `tasks`, `task_assignees`, `task_comments`; Migration `2026_12_25_000001_create_task_system.php` übernimmt `board_tasks` und löscht diese Tabellen.
- Nach dem Einspielen `php artisan portal:update`.
- Keine E-Mail-Benachrichtigungen (Erinnerungen an Fälligkeit wären ein möglicher Ausbau).
