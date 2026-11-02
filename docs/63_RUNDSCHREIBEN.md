# Rundschreiben und Mitteilungen per E-Mail

Modul `app/Modules/Mailings`. Zwei Wege, ein Platzhalter-System.

## Vorstand: Rundschreiben an Mitglieder
- Seite „Rundschreiben“ im Bereich Vorstand (`/vorstand/rundschreiben`), Berechtigung `mailings.members` (nur Vorstand).
- Der Vorstand schreibt **frei**: Betreff und Text (Editor), dazu optional Angaben zur Versammlung (Titel, Datum, Uhrzeit, Ort, Link, Frist).
- Ablauf: Schreiben → Vorschau (mit den Angaben der schreibenden Person eingesetzt) → optional Testmail an sich selbst → Senden.
- Empfänger: alle laufenden Mitgliedschaften mit E-Mail-Adresse (Person oder an der Mitgliedschaft hinterlegt), jede Adresse einmal. Nicht abbestellbar, weil Vereinspost (Einladung zur Versammlung).

## Koordination: Mitteilungen an Teamende
- Seite „Mitteilungen an Teamende“ im Bereich Koordination (`/koordination/mitteilungen`), Berechtigung `mailings.team` (Rolle Koordination).
- Die **Vorlage** (Betreff und Text) pflegt die Geschäftsstelle unter „Mailvorlagen“ (`/geschaeftsstelle/vorlagen`, Berechtigung `mail_templates.manage`: Geschäftsstelle und Vorstand). Ohne gespeicherte Fassung gilt die eingebaute.
- Die Koordination wählt ein Seminar (in Planung, terminiert oder bestätigt), die Empfänger und schreibt einen **eigenen Textabschnitt**, der an der Stelle `{eigener_text}` der Vorlage steht (fehlt die Stelle, wird er angehängt).
- Empfänger: alle aktiven Teamenden, nur die mit Interesse an diesem Seminar oder nur die im Team des Seminars. Wer unter Konto › Benachrichtigungen „Mitteilungen der Koordination“ abbestellt hat, bekommt sie nicht.

## Platzhalter
Geschrieben als `{name}`. Unbekannte werden beim Speichern und Senden abgelehnt.

| Art | Platzhalter |
|---|---|
| Alle | `{vorname}` `{nachname}` `{name}` `{absender}` `{verein}` `{portal}` |
| Mitglieder | `{versammlung}` `{termin}` `{datum}` `{uhrzeit}` `{ort}` `{link}` `{frist}` |
| Teamende | `{seminar}` `{schule}` `{termine}` `{erster_termin}` `{ort}` `{stichtag}` `{link}` `{eigener_text}` |

Wer einen Platzhalter der Versammlung nutzt, muss die Angabe machen. Werte werden maskiert eingesetzt (nur `{termine}` und `{eigener_text}` sind bereinigtes HTML).

## Datenschutz und Protokoll
Gespeichert werden Betreff, Text mit Platzhaltern, Zahl der Empfänger und Zahl der Fehlschläge, nie die Empfängerliste. Das Protokoll hält nur Art und Zahlen fest („Schreiben versendet“).

## Grenzen
Der Versand läuft direkt im Seitenaufruf, eine Mail nach der anderen (pro Aufruf Zeitlimit des Servers). Für sehr viele Empfänger braucht es später einen Queue-Worker. Fehlgeschlagene Zustellungen stehen im Fehlerprotokoll der Systemsteuerung.
