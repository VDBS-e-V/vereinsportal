# Systemsteuerung (Bereich Admins)

Die Systemsteuerung ist der technische Betriebsbereich der Rolle **Administration**. Sie liegt unter `/admins/system` und ist im Menü des Bereichs Admins als Untermenü „System“ erreichbar. Die Berechtigung heißt `system.manage` und gehört ausschließlich der Rolle Administration (Zwei-Faktor ist für diese Rolle Pflicht). Verwaltungsseiten (Nutzer, Artikel, Seiten, …) bleiben im Bereich Geschäftsstelle.

## Seiten

| Seite | Adresse | Zweck |
|---|---|---|
| Zustand | `/admins/system` | Prüfungen mit Ampel: Umgebung, Debug-Modus, PHP-Erweiterungen, Datenbank, Migrationen, Schreibrechte, Speicher, Frontend-Build, E-Mail, Planer, Warteschlange. Nur lesen. |
| Aufgaben | `/admins/system/aufgaben` | Feste Liste von Wartungsaufgaben (Cache leeren, Migrationen, geplante Aufgaben einzeln, Aufbewahrungsfristen, Wartungsmodus). Letzte Läufe mit bereinigter Ausgabe. |
| Fehlerprotokoll | `/admins/system/fehlerprotokoll` | Ende der Datei `storage/logs/*.log`, neueste Einträge zuerst, Filter nach Stufe und Suchtext. Nur lesen. |
| Warteschlange | `/admins/system/warteschlange` | Fehlgeschlagene Aufträge erneut versuchen, verwerfen oder alle verwerfen (mit Passwort). |
| Einstellungen | `/admins/system/einstellungen` | Geltende Werte (Fristen, E-Mail, Betrieb) mit dem `.env`-Schlüssel dazu, außerdem eine Testmail. Das Portal schreibt die `.env` nicht. |

## Schutzstufen der Aufgaben

- **Unkritisch**: Cache, Konfiguration, Ansichten leeren. Ein Klick.
- **Mit Bestätigung**: Zwischenspeicher für den Betrieb, einzelne tägliche Aufgaben (versenden z. B. Erinnerungen), Wartungsmodus beenden.
- **Mit Passwort**: Migrationen, alle Aufräum-Aufgaben nach Aufbewahrungsfrist, Wartungsmodus einschalten.

Es gibt kein freies Befehlsfeld. Nie dabei: `migrate:fresh`, `db:wipe`. Pro Zeitpunkt läuft eine Aufgabe (Sperre). Jede Ausführung steht im Protokoll (Ereignis „Systemaufgabe ausgeführt“, nur mit Aufgabenname und Ergebnis). Ausgaben werden um Passwörter, Schlüssel und Tokens bereinigt und 90 Tage aufbewahrt.

## Planer (Scheduler)

Die täglichen Aufgaben laufen nur, wenn auf dem Server jede Minute `php artisan schedule:run` startet (Cron). Lokal: `php artisan schedule:work`. Die Aufgabe `system-heartbeat` setzt jede Minute einen Zeitstempel; die Seite Zustand zeigt daran, ob der Planer läuft.

## Konsole

- `php artisan role:grant-administration <E-Mail>`: gibt einem bestehenden Konto die Rolle Administration (nur Konsole, mit Protokolleintrag). Nötig für den Start, weil die Rolle im Portal nur eine Administration vergibt.

## Grenzen

- Aufgaben laufen direkt im Seitenaufruf (Zeitlimit 180 Sekunden), nicht über einen Worker.
- Build (`npm run build`) und Updates per Git gehören nicht in die Oberfläche.
- Das Fehlerprotokoll liest nur das Ende der neuesten Datei (2 MB).

## Code

Modul `app/Modules/System`: Controller (`SystemController`, `SystemLogController`, `SystemQueueController`, `SystemSettingsController`), Dienste (`SystemChecks`, `SystemTasks`, `SystemLogs`, `SystemSettings`), `Support\Redactor`, Modell `SystemTaskRun`. Tests: `tests/Feature/System/SystemTest.php`.
