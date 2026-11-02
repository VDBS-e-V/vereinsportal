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

## Abmelden und Sperren (seit Update 2)
- **Abmeldelink:** Jede abbestellbare Mail enthält im Fuß „Mitteilungen dieser Art abbestellen“ und die Kopfzeilen `List-Unsubscribe` und `List-Unsubscribe-Post: List-Unsubscribe=One-Click` (RFC 2369 und RFC 8058). Der Link `/abmelden/{Token}` enthält Adresse und Thema **verschlüsselt** und funktioniert ohne Anmeldung. Die Seite zeigt nur die verkürzte Adresse (`a***@…`), meldet mit einem Klick ab und lässt sich rückgängig machen. Der POST nimmt auch den One-Click-Aufruf der Postfachanbieter entgegen (ohne CSRF-Token, Drosselung 60 pro Minute).
- **Themen:** Mitglieder (`member_info`), Teamende (`team_mailings`), Schulen (`school_mailings`). Abmeldung gilt je Thema. Interne Gruppen (Koordination, Bildungsreferenten, Vorstand, Geschäftsstelle) sind Organisationspost und nicht abbestellbar, die Mails haben keinen Abmeldelink.
- **Konto und Link im Gleichschritt:** Teamende und Schulen mit Konto: Die Abmeldung per Link setzt die Einstellung unter Konto › Benachrichtigungen und umgekehrt.
- **Pflichtmitteilung** (nur Vorstand): Kreuzfeld „Pflichtmitteilung (nicht abbestellbar)“ im Formular. Die Mail geht auch an Abgemeldete, enthält keinen Abmeldelink und trägt im Fuß den Hinweis, dass sie nicht abbestellt werden kann. Für Einladungen zur Mitgliederversammlung gedacht.
- **Gesperrte Adressen** (Vorstand und Geschäftsstelle, Seite „Gesperrte Adressen“ in der Rundschreiben-Liste): Gesperrte bekommen **nie** ein Schreiben an Gruppen, auch keine Pflichtmitteilung. Sperren manuell setzen und aufheben; Gründe „Dauerhafter Zustellfehler“ und „Beschwerde“ sind für die späteren Bounce-Updates vorbereitet. Das allgemeine Protokoll nennt nur Aktion und Grund, nie die Adresse.
- **Zahlen vor dem Senden:** Das Formular zeigt Erreichbare, Abgemeldete und Gesperrte.
- **Prüfung beim Senden:** Der Worker prüft jede Adresse noch einmal unmittelbar vor dem Versand. Wer sich nach dem Anlegen abmeldet oder gesperrt wird, wird übersprungen (Zeile „übersprungen“ mit Grund).

## Entwürfe, Planung und Freigabe (seit Update 3)

- **Entwurf:** Im Formular „Als Entwurf speichern“. Entwürfe sieht nur die schreibende Person; sie öffnet ihn unter „In Vorbereitung“ wieder und kann ihn ändern oder löschen.
- **Planung:** Das Feld „Senden am“ plant den Versand. Der Planer (`mailings:dispatch-due`, jede Minute) startet das Schreiben zur Zeit. Die Empfänger werden erst dann bestimmt, Abmeldungen und Sperren gelten also bis zuletzt. Ein geplantes Schreiben lässt sich zurückziehen.
- **Freigabe (Vier-Augen-Prinzip):** Ab `MAILINGS_APPROVAL_THRESHOLD` Empfängern (Standard 50) legt das Schreiben Personen ohne Recht `mailings.approve` zur Freigabe vor. Der Vorstand wird benachrichtigt (Einstellung „Schreiben zur Freigabe“), gibt frei oder mit Begründung zurück. Das eigene Schreiben lässt sich nicht freigeben. Der Vorstand schreibt frei. Abschalten: `MAILINGS_APPROVAL=false`.
- **Absender je Bereich:** `MAILINGS_FROM_NAME_BOARD/OFFICE/TEAM` und `MAILINGS_REPLY_TO_BOARD/OFFICE/TEAM`. Die Absenderadresse bleibt die des Versanddienstes.
- Jeder Schritt (vorgelegt, freigegeben, zurückgegeben, geplant, zurückgezogen) steht im Protokoll („Schreiben: Freigabe und Planung“).

## Grenzen
Stürzt ein Worker mitten in einer Mail ab, kann diese eine Mail doppelt zugestellt werden (Zeilen im Zustand „wird gesendet“ werden nach 10 Minuten wieder freigegeben). Bounces folgen im nächsten Update (siehe `docs/65_RUNDSCHREIBEN_ENTWICKLUNGSPLAN.md`).
