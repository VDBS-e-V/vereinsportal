# Vorstandssitzungen

Unter **Vorstand › Sitzungen** (`/vorstand/sitzungen`) verwaltet der Vorstand seine Sitzungen im Portal. Nur Vorstandsmitglieder (Berechtigung `board.meetings`) sehen und ändern sie.

- **Sitzung:** Titel, Datum, Beginn und Ende, Ort, Link zur Videokonferenz (`https://`), Stand (geplant, stattgefunden, abgesagt).
- **Tagesordnung** und **Protokoll:** Textfelder mit Formatierung. Das Protokoll lässt sich mit „Als genehmigt vermerken“ (meist in der nächsten Sitzung) als genehmigt kennzeichnen. Ändert jemand ein genehmigtes Protokoll, gilt es wieder als nicht genehmigt.
- **Anwesenheit:** Mehrfachauswahl der Vorstandsmitglieder für „Anwesend“ und „Entschuldigt“.
- **Drucken:** Auf der Sitzungsseite druckt oder speichert „Drucken“ Tagesordnung und Protokoll als PDF.
- **Löschen:** einzeln oder mit Mehrfachauswahl in der Liste.
- **Protokoll der Änderungen:** Speichern, Löschen, Genehmigen und Erneuern der Abo-Adresse stehen im Portalprotokoll (Gruppe „Vorstand“).

## Kalender-Abo

Auf der Seite steht eine geheime **Abo-Adresse** (`/vorstand/kalender/<Schlüssel>.ics`). Jedes Vorstandsmitglied trägt sie einmal in Outlook, Apple Kalender, Thunderbird, Google Kalender oder Nextcloud ein; neue und geänderte Sitzungen erscheinen von selbst (Abgesagte als „abgesagt“). Der Kalender enthält Titel, Zeit, Ort und Videolink, **nie** Tagesordnung oder Protokoll. Zeiten sind Berliner Ortszeit.

Kalenderprogramme melden sich nicht im Portal an, deshalb ist die Adresse ohne Anmeldung abrufbar und nur durch den Schlüssel geschützt. Wurde sie weitergegeben, erneuert „Adresse erneuern“ den Schlüssel; die alte Adresse liefert dann nichts mehr.

Zusätzlich lässt sich ein **externer Kalenderlink** (`https://`) hinterlegen, falls der Vorstand außerhalb des Portals einen weiteren Kalender nutzt.

Neue Tabellen `board_settings`, `board_meetings`, `board_meeting_user`: nach dem Einspielen `php artisan portal:update` ausführen.
