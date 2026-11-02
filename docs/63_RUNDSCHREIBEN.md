# Rundschreiben und Mitteilungen per E-Mail

Modul `app/Modules/Mailings`. Ein Platzhalter-System, drei Wege.

## 1. Rundschreiben an Gruppen (Vorstand und Geschäftsstelle)
- Zwei Seiten, eine Klasse (`GroupMailingController`): im Bereich **Vorstand** (`/vorstand/mitteilungen`, Berechtigung `mailings.members`) und im Bereich **Geschäftsstelle** (`/geschaeftsstelle/mitteilungen`, Berechtigung `mailings.send`).
- Gruppen zur Auswahl (mehrere möglich, jede Adresse einmal): Mitglieder, Teamende, Schulen, Koordination, Bildungsreferenten, Vorstand, Geschäftsstelle.
  - **Mitglieder** darf nur der Vorstand anschreiben (`mailings.members`). Die Mail kommt aus den laufenden Mitgliedschaften mit E-Mail-Adresse und ist nicht abbestellbar (Vereinspost).
  - Alle anderen Gruppen sind aktive Konten mit der jeweiligen Rolle. **Teamende** und **Schulen** können die Mitteilungen in Konto › Benachrichtigungen abbestellen; wer das getan hat, wird nicht gezählt und nicht angeschrieben.
- Schreiben: frei im Editor mit Platzhaltern, **oder** eine Vorlage laden (dann Betreff und Text ändern). Optional Angaben zu Termin oder Versammlung (Titel, Datum, Uhrzeit, Ort, Link, Frist).
- Ablauf: Gruppen wählen → schreiben → Vorschau (mit den Angaben der schreibenden Person eingesetzt) → optional Testmail an sich → Senden.

## 2. Mailvorlagen (Geschäftsstelle und Vorstand, `mail_templates.manage`)
- `/geschaeftsstelle/vorlagen`: eigene Vorlagen für Rundschreiben anlegen, ändern, löschen (Name, Beschreibung, Betreff, Text).
- Dort auch der **feste Rahmen für Mitteilungen an Teamende zu Seminaren** (`/geschaeftsstelle/vorlagen/seminar`). Ohne gespeicherte Fassung gilt die eingebaute.

## 3. Koordination: Mitteilungen an Teamende zu Seminaren
- `/koordination/mitteilungen`, Berechtigung `mailings.team` (Rolle Koordination).
- Seminar wählen (in Planung, terminiert oder bestätigt), Empfänger wählen (alle Teamenden, Interesse an diesem Seminar, im Team dieses Seminars), eigenen **Textabschnitt** schreiben. Er steht an der Stelle `{eigener_text}` der Vorlage der Geschäftsstelle (fehlt die Stelle, wird er angehängt).

## Platzhalter
Geschrieben als `{name}`. Unbekannte werden beim Speichern und Senden abgelehnt.

| Art | Platzhalter |
|---|---|
| Alle | `{vorname}` `{nachname}` `{name}` `{absender}` `{verein}` `{portal}` |
| Rundschreiben | `{versammlung}` `{termin}` `{datum}` `{uhrzeit}` `{ort}` `{link}` `{frist}` |
| Seminar-Mitteilungen | `{seminar}` `{schule}` `{termine}` `{erster_termin}` `{ort}` `{stichtag}` `{link}` `{eigener_text}` |

Wer einen Platzhalter des Termins nutzt, muss die Angabe machen. Fehlt der Vorname einer Person, steht eine neutrale Anrede der Gruppe (z. B. „Mitglied“, „Teamer:in“, „Schule“). Werte werden maskiert eingesetzt (nur `{termine}` und `{eigener_text}` sind bereinigtes HTML).

## Datenschutz und Protokoll
Gespeichert werden Betreff, Text mit Platzhaltern, Gruppen und Zahl der Empfänger und Fehlschläge, nie die Empfängerliste. Das Protokoll hält nur Art, Gruppen und Zahlen fest („Schreiben versendet“).

## Versand im Hintergrund (seit Update 1)
- Ein Schreiben wird in die **Warteschlange** gestellt und von einem Worker versendet: lokal `php artisan queue:work`, produktiv als Dienst (z. B. Supervisor). Mit `QUEUE_CONNECTION=sync` läuft der Versand sofort im Seitenaufruf (Tests, kleine Gruppen).
- Je Adresse gibt es eine **Zustellzeile** (wartend, wird gesendet, gesendet, fehlgeschlagen, übersprungen) mit Versuchen und Fehlergrund. Eine Zeile wird atomar „in Arbeit“ gesetzt, bevor gesendet wird: Ein wiederholter Lauf sendet nie doppelt.
- Der Versand läuft in Paketen (`MAILINGS_BATCH_SIZE`, Standard 25) mit Drosselung (`MAILINGS_PER_MINUTE`, Standard 60) und bis zu `MAILINGS_MAX_ATTEMPTS` (3) Versuchen je Adresse.
- Die **Versandseite** (Klick auf ein Schreiben in der Liste) zeigt Stand, Zähler, Fortschritt und die fehlgeschlagenen Adressen (nur für die schreibende Person und den Vorstand), dazu „Versand abbrechen“ und „Fehlgeschlagene erneut versuchen“. Sie aktualisiert sich selbst, solange der Versand läuft.
- **Aufbewahrung:** Die Zustelllisten mit den Adressen werden nach `MAILINGS_DELIVERY_RETENTION_DAYS` (Standard 90) von `php artisan mailings:prune` gelöscht (täglich im Planer). Betreff, Text und Zahlen bleiben.
- Systemsteuerung › Zustand warnt, wenn ein Auftrag lange wartet oder ein Schreiben über 15 Minuten nicht versendet wurde (Hinweis auf den Worker).

## Grenzen
Stürzt ein Worker mitten in einer Mail ab, kann diese eine Mail doppelt zugestellt werden (Zeilen im Zustand „wird gesendet“ werden nach 10 Minuten wieder freigegeben). Bounces und Abmeldelinks folgen in den nächsten Updates (siehe `docs/65_RUNDSCHREIBEN_ENTWICKLUNGSPLAN.md`).
