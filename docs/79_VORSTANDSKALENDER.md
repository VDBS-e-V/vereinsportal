# Vorstandskalender

Der Vorstand hat unter **Vorstand › Kalender** (`/vorstand/kalender`) einen gemeinsamen Kalenderlink für die Vorstandssitzungen.

- **Link zum Kalender:** Freigabe-Adresse eines Kalenders (Nextcloud, Google, Outlook). Ein Klick öffnet ihn in einem neuen Tab.
- **Abo-Adresse (iCal), optional:** Adresse mit der Endung `.ics`. Daneben erscheinen ein Knopf „Abonnieren“ (`webcal://`) und die Adresse zum Kopieren.
- **Hinweis, optional:** Zum Beispiel Sitzungsrhythmus oder Treffpunkt.
- Nur `https://`-Adressen werden gespeichert. Sehen und ändern dürfen es nur Vorstandsmitglieder (Berechtigung `board.calendar`). Jede Änderung steht im Protokoll (Gruppe „Vorstand“).
- Das Portal pflegt keine Termine selbst: Die Sitzungen tragen Sie im verlinkten Kalender ein.
- Neue Tabelle `board_settings`: nach dem Einspielen `php artisan portal:update` (oder `migrate`) ausführen.
