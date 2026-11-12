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

## Labels

Jedes Projekt hat eigene Labels (Name und eine der sechs Portalfarben bzw. Grau), verwaltet unter *Einstellungen › Labels*. Labels erscheinen als Badges auf Karte, Liste und Aufgabe. Filter in beiden Ansichten; in der Liste lassen sich Labels per Mehrfachauswahl vergeben. Löschen eines Labels entfernt es von allen Aufgaben.

## Unteraufgaben

Eine Aufgabe hat eine Checkliste aus Unteraufgaben (Haken, bis zu 100). Mehrere Zeilen im Feld legen mehrere Unteraufgaben an. Der Fortschritt („2/5“) steht auf Karte und Liste. Unteraufgaben sind einfache Schritte ohne eigene Zuständige oder Fristen.

## Anhänge

An jeder Aufgabe lassen sich bis zu 20 Dateien auf einmal ablegen (PDF, Word, Excel, CSV, OpenDocument, ZIP, Text; keine Bilder; Größengrenzen wie bei Seminardokumenten). Sie liegen privat im Portal. Löschen mit Mehrfachauswahl: eigene Anhänge jede Person, fremde die Verwaltenden. Beim Löschen einer Aufgabe oder eines Projekts verschwinden auch die Dateien.

## Vorlagen

Unter *Vorlagen* im Projekt (Verwaltende pflegen sie, alle Mitglieder nutzen sie): Name, Titel, Beschreibung, Priorität, „fällig nach X Tagen“, Labels und Unteraufgaben (eine Zeile je Schritt). „Aus Vorlage anlegen“ erzeugt die Aufgabe mit eigenem Titel, Zuständigen und Fälligkeit nach Wunsch. Eine bestehende Aufgabe lässt sich mit „Als Vorlage speichern“ übernehmen (ohne Zuständige und Fälligkeit).

## E-Mail-Erinnerungen (jede Person entscheidet selbst)

Im Konto unter *Benachrichtigungen › Aufgaben* gibt es zwei Schalter, beide **standardmäßig aus**:

- *Neue Aufgaben, die Ihnen zugewiesen werden:* sofort eine Mail; wer sich selbst zuweist, bekommt keine; mehrere Zuweisungen in einer Sammelaktion kommen in einer Mail.
- *Erinnerung an fällige Aufgaben:* der Befehl `tasks:remind` läuft täglich um 07:30 (auch als Aufgabe „An fällige Aufgaben erinnern“ in der Systemsteuerung, `--dry-run` zählt nur) und schickt je Person eine Mail über Aufgaben, die heute oder morgen fällig sind, und über überfällige, jeweils einmal je Aufgabe und Person.

Die Mails nennen Titel und Projekt der Aufgaben, aber keine Beschreibung oder Kommentare.

## Aufgabe

Titel, Beschreibung, Priorität (niedrig, normal, hoch), Fälligkeit, mehrere Zuständige (nur Mitglieder des Projekts), Spalte, Labels, Unteraufgaben, Anhänge, Kommentare. Im Projekt Vorstand zusätzlich Bezug zu Sitzung und Beschluss; die Sitzungsseite legt Aufgaben dort an und zeigt sie.

## Datenschutz

Name der Zuständigen, Kommentare und Anhänge werden gespeichert; das Portalprotokoll (Gruppe „Aufgaben“) nennt Titel und Projekt, nie Beschreibung oder Kommentar. Es gibt keine automatische Löschung; Aufgaben und Projekte löscht die Verwaltung. Die Datenschutzerklärung enthält den Abschnitt „Aufgabenverwaltung“. Bereits gespeicherte Datenschutzseiten müssen ihn von Hand erhalten.

## Technik

- Tabellen `task_projects`, `task_project_members`, `task_statuses`, `tasks`, `task_assignees`, `task_comments`; Migration `2026_12_25_000001_create_task_system.php` übernimmt `board_tasks` und löscht diese Tabellen.
- Nach dem Einspielen `php artisan portal:update`.
- Neue Tabellen `task_labels`, `task_label_task`, `task_subtasks`, `task_attachments`, `task_templates`, `task_template_label` (Migration `2026_12_26_000001_extend_task_system.php`) und zwei Merker-Spalten an `task_assignees`.
- **Designsystem:** Die Kanban-Tafel ist ein Baustein des Pakets „Daten“: `<x-vdbs.data.kanban>`, `<x-vdbs.data.kanban-column>`, `<x-vdbs.data.kanban-card>` (CSS unter `resources/css/vdbs/components/data/kanban*.css`, JavaScript `resources/js/kanban.js`, Beispielseiten in der Design-Bibliothek, Tests in `tests/Feature/DesignLibrary/Packages/DataTest.php`). Nach dem Einspielen muss das Frontend neu gebaut werden (`npm run build`); das frühere `public/vendor/kanban/kanban.js` entfällt.
