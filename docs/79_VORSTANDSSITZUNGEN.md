# Vorstandssitzungen

Unter **Vorstand › Sitzungen** (`/vorstand/sitzungen`) verwaltet der Vorstand seine Sitzungen im Portal. Nur Vorstandsmitglieder (Berechtigung `board.meetings`) sehen und ändern sie.

- **Sitzung:** Titel, Datum, Beginn und Ende, Ort, Link zur Videokonferenz (`https://`), Stand (geplant, stattgefunden, abgesagt).
- **Tagesordnung** und **Protokoll:** Textfelder mit Formatierung. Das Protokoll lässt sich mit „Als genehmigt vermerken“ (meist in der nächsten Sitzung) als genehmigt kennzeichnen. Ändert jemand ein genehmigtes Protokoll, gilt es wieder als nicht genehmigt.
- **Anwesenheit:** Mehrfachauswahl der Vorstandsmitglieder für „Anwesend“ und „Entschuldigt“.
- **Anhänge:** Auf der Sitzungsseite lassen sich bis zu 20 Dateien auf einmal hochladen (PDF, Word, Excel, CSV, OpenDocument, ZIP, Text; keine Bilder), zum Beispiel das unterschriebene Protokoll als PDF. Sie liegen privat im Portal, es gelten die Größengrenzen der Seminardokumente. Löschen geht einzeln oder mit Mehrfachauswahl; beim Löschen einer Sitzung verschwinden auch ihre Dateien.
- **Externe Gäste:** Auf der Sitzungsseite lassen sich Gäste ohne Portalkonto eintragen (Name, E-Mail-Adresse, Organisation), zum Beispiel Beratende oder Partner. Sie sehen nichts im Portal; sie bekommen nur die Einladung. Entfernen geht einzeln oder mit Mehrfachauswahl. **Datenschutz:** Die Angaben der Gäste werden **30 Tage nach Ende der Sitzung automatisch gelöscht** (`board:prune-guests`, täglich geplant, auch als Aufgabe „Gäste der Vorstandssitzungen aufräumen"; Wert einstellbar mit `BOARD_GUEST_RETENTION_DAYS`) und auf Wunsch sofort. Tragen Sie nur ein, wer eingeladen werden soll. Die Einladung enthält den Hinweis zum Datenschutz. Einzelheiten und Entwurf für das Verzeichnis der Verarbeitungstätigkeiten: `docs/80_DATENSCHUTZ_VORSTANDSSITZUNGEN.md`.
- **Drucken:** Auf der Sitzungsseite druckt oder speichert „Drucken“ Tagesordnung und Protokoll als PDF.
- **Löschen:** einzeln oder mit Mehrfachauswahl in der Liste.
- **Protokoll der Änderungen:** Speichern, Löschen, Genehmigen und Erneuern der Abo-Adresse stehen im Portalprotokoll (Gruppe „Vorstand“).

## Kalender-Abo

Auf der Seite steht eine geheime **Abo-Adresse** (`/vorstand/kalender/<Schlüssel>.ics`). Jedes Vorstandsmitglied trägt sie einmal in Outlook, Apple Kalender, Thunderbird, Google Kalender oder Nextcloud ein; neue und geänderte Sitzungen erscheinen von selbst (Abgesagte als „abgesagt“). Der Kalender enthält Titel, Zeit, Ort und Videolink, **nie** Tagesordnung oder Protokoll. Zeiten sind Berliner Ortszeit.

Kalenderprogramme melden sich nicht im Portal an, deshalb ist die Adresse ohne Anmeldung abrufbar und nur durch den Schlüssel geschützt. Wurde sie weitergegeben, erneuert „Adresse erneuern“ den Schlüssel; die alte Adresse liefert dann nichts mehr.

Zusätzlich lässt sich ein **externer Kalenderlink** (`https://`) hinterlegen, falls der Vorstand außerhalb des Portals einen weiteren Kalender nutzt.

## Einladungen per E-Mail (abschaltbar)

Auf der Seite „Sitzungen“ steht der Schalter **Einladungen per E-Mail**. Er ist **zunächst aus**; solange er aus ist, gibt es nirgends einen Einladungsknopf und es wird nichts verschickt.

- Eingeschaltet erscheint auf jeder Sitzungsseite „Einladung senden“ (mit optionaler Nachricht, zum Beispiel bei einer Terminänderung) und im Formular das Häkchen „Nach dem Speichern einladen“. Es wird nie von selbst eingeladen.
- Die E-Mail enthält Termin, Ort, Videolink, Tagesordnung als Text, den Link zur Sitzung im Portal und den Termin als Kalenderdatei (`.ics`) im Anhang. Protokoll und Anhänge stehen nicht in der Mail.
- Empfänger sind aktive Konten mit Rolle Vorstand. Wer die Benachrichtigung „Einladungen zu Vorstandssitzungen“ im Konto abstellt, bekommt keine.
- Abgesagte Sitzungen werden nicht eingeladen. Wird der Termin nach der Einladung geändert, zeigt die Sitzungsseite „Seitdem geändert“; „Erneut einladen“ schickt eine neue Einladung.
- Jede Einladung steht im Portalprotokoll (Anzahl der Empfänger), ebenso das Ein- und Ausschalten.

### Einladung für Gäste

Mit eingeschalteten Einladungen steht unter der Gästeliste „Auswahl einladen“ (ohne Auswahl: alle Gäste). Die Mail enthält Termin, Ort, Videolink, optional eine Nachricht und die Kalenderdatei (`.ics`). Es steht **kein Portallink** darin, und die **Tagesordnung nur, wenn Sie „Tagesordnung in die E-Mail aufnehmen“ ankreuzen**. Es gilt derselbe Schalter wie für den Vorstand; ausgeschaltet verschickt das Portal nichts. In der Liste steht, wann ein Gast eingeladen wurde. Im Protokoll steht die Anzahl, nicht die Adressen.

Neue Tabellen `board_settings`, `board_meetings`, `board_meeting_user`, `board_meeting_documents`, `board_meeting_guests`: nach dem Einspielen `php artisan portal:update` ausführen.
