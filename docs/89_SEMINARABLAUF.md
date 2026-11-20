# Seminarablauf (neu aufgebaut)

Stand: umgesetzt in den Phasen 1 bis 12 (Branch `claude/cool-ramanujan-fgmbcu`). Der frühere Seminarablauf (Doc 86/87, Stand `f8866bb`) wurde vorher entfernt (Doc 88); diese Umsetzung ist neu geschrieben. **Alles ist nur durch Tests in der CI geprüft; im Browser und gegen eine echte Nextcloud ist noch nichts probiert.**

## Festlegungen

1. Verbindlich wird ein Seminar mit dem **unterschriebenen Schulvertrag**. Den Vertrag stellt die Schule; das Portal speichert ihn nur (Upload mit Unterschriftsdatum, durch Schule oder Geschäftsstelle). Alternativ gibt die Geschäftsstelle auf **verbindliche Zusage der Schule** frei (Begründung Pflicht, Vertrag darf folgen).
2. Das Portal erstellt nur den **Kostenvoranschlag (KVA)**: Preisliste, Textbausteine, Versionen, interne Freigabe durch Vorstand oder Koordination, Versand per Mail mit PDF.
3. Die Sollzahl des Teams legt die Koordination je Seminar fest. Die Auswahl ist ein **Entwurf**; erst „Team bestätigen“ sendet alle Zusagen, Absagen und Wartelistenplätze zugleich. Die Seminarleitung ist eine Person aus dem Team.
4. **Koordinationen** sind Datensätze (Verwaltung › Koordinationen); die Schule wählt ihre Koordination per Liste.
5. Die Teamenden füllen ihren Vertrag außerhalb des Portals aus. Die **Seminarleitung lädt ihn hoch** und trägt den Betrag ein; daraus entsteht ein **Zahlungsauftrag** an die Geschäftsstelle (Buchhaltung).
6. Der **Druckplaner** liefert eine einzige PDF für einen Druckauftrag (Deckblatt, erst Schwarzweiß, dann Farbe, Exemplare und Seiten pro Blatt eingerechnet). Die Seminarmappe gibt es zusätzlich als ZIP.
7. **Nextcloud:** Das Portal legt `Koordination/<Koordination>/<Schule>/<Schuljahr>/<Nummer Titel>/Ergebnisse` an und gibt nur „Ergebnisse“ mit Ablaufdatum für die Schule frei.

## Status

Anfrage → In Prüfung → KVA gesendet → Freigegeben → Geplant → Ausgeschrieben → Team gewählt → Team bestätigt → Vorbereitet → Durchgeführt → Berichtet → Abgeschlossen. Endzustände: Abgelehnt, Abgesagt. Alle Übergänge, Rechte und Begründungspflichten stehen in `SeminarWorkflow`; kein Controller ändert den Status selbst. Jeder Wechsel steht im Verlauf des Seminars und im Protokoll.

Eine Anfrage kommt entweder von der Schule selbst (Bereich Schulen) oder wird von Koordination, Geschäftsstelle oder Vorstand im Namen der Schule eingetragen („Anfrage eintragen“, Recht `seminars.enter`, z. B. nach einem Anruf). Im zweiten Fall bekommt die Schule keine Eingangsbestätigung, nur die Geschäftsstelle den Hinweis; der Verlauf trägt „Im Auftrag der Schule eingetragen“. Ohne eigene Angabe wird die Ansprechperson der Schule übernommen. Die Koordination sieht eingetragene Anfragen schreibgeschützt unter „Anfragen in Prüfung“, bis sie freigegeben sind.

## Seiten

| Wer | Seite |
|---|---|
| Schule | Seminare (Anfrage, Stand, KVA annehmen/ablehnen/nachfragen, Vertrag hochladen, Bericht, Rückmeldung) |
| Geschäftsstelle | Seminare (Liste, Prüfung, KVA, Vertrag, Freigabe), Preisliste, Zahlungsaufträge |
| Vorstand, Koordination | KVA-Freigabe |
| Koordination | Seminare planen, Team auswählen, Vorbereitung, Bericht prüfen und senden, Rückmeldungen |
| Teamende | Seminare (Interesse melden, eigene Ergebnisse), Meine Leitung |
| Alle Beteiligten | Druckplaner und Seminarmappe |

## Mitteilungen

Immer per E-Mail. Nicht abschaltbar: KVA, Zusage, Absage, Warteliste und alles an die Schule. Abschaltbar unter „Benachrichtigungen“ (Konto): Seminaranfragen, Freigaben, Ausschreibungen und Fristerinnerungen, Hinweise zu eigenen Seminaren, Berichtserinnerungen, überfällige Berichte, Zahlungsaufträge.

## Geplante Läufe

`seminars:remind-deadlines` (Bewerbungsfrist), `seminars:remind-reports` (Stichtag Bericht), `seminars:results-expiry` (Ergebnislink läuft ab), `seminars:prune-requests` (alte Anfragen ohne Vertrag). Alle laufen täglich und sind in „System › Aufgaben“ von Hand auslösbar.

## Fristen

- Ergebnislink und Ergebnisordner: Standard 2 Jahre nach dem letzten Termin, mindestens 1 Jahr, verlängerbar; 30 Tage vorher kommt ein Hinweis. Gelöscht wird nichts von selbst.
- Abgelehnte oder abgesagte Anfragen ohne Vertrag: 1 Jahr nach der letzten Änderung (`SEMINAR_REQUEST_YEARS`).
- Seminare mit Schulvertrag, Freigabe oder Zahlungsauftrag werden nicht automatisch gelöscht. **Verträge und Belege mit Geldbezug folgen den gesetzlichen Fristen; sie sind mit der Steuerberatung zu klären.**

## Einrichtung der Nextcloud-Anbindung

1. In der Nextcloud: Team folder „Koordination“ und ein Dienstkonto mit Schreibrecht darauf; App-Passwort erzeugen.
2. In der Umgebung (`.env`, nie im Chat oder im Code): `NEXTCLOUD_URL`, `NEXTCLOUD_SERVICE_USER`, `NEXTCLOUD_SERVICE_PASSWORD`, optional `NEXTCLOUD_TEAM_FOLDER`.
3. Der Queue-Worker (`php artisan queue:work`) muss laufen, sonst bleibt der Ordnerauftrag liegen. Alternativ „Ordner in der Nextcloud anlegen“ am Seminar.
4. Ohne Zugangsdaten bleibt alles beim Alten: Der Ordner wird von Hand angelegt und den Ergebnislink trägt die Koordination selbst ein. Die Teamenden verwaltet die Nextcloud selbst (keine Zugriffsregeln durch das Portal).

## Grenzen und offene Punkte

- Der Druckplaner nutzt `setasign/fpdi`. PDFs mit komprimierter Querverweistabelle (PDF 1.5 und neuer) lassen sich damit nicht importieren; sie erscheinen als „nicht im Druckplaner“ und müssen als PDF 1.4 neu gespeichert werden. Schwarzweiß oder Farbe steht nur auf dem Deckblatt und in der Reihenfolge (erst Schwarzweiß).
- Bewerben können sich nur Konten mit der Rolle Teamende; einen Bewerbungslink für Personen ohne Konto gibt es nicht.
- Die KVA-Freigabe hat in der Kopfzeile nur für die Koordination einen Menüpunkt; Vorstandsmitglieder erreichen die Seite über die Adresse `/kva-freigabe`.
- Bankdaten speichert das Portal nicht; sie stehen im hochgeladenen Vertrag. Der Zugriff darauf ist auf Geschäftsstelle, Buchhaltung, Seminarleitung und Koordination begrenzt.
