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

## Grenzen
Der Versand läuft direkt im Seitenaufruf, eine Mail nach der anderen (Zeitlimit des Servers). Für sehr viele Empfänger braucht es später einen Queue-Worker. Fehlgeschlagene Zustellungen stehen im Fehlerprotokoll der Systemsteuerung.
