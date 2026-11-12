# 56 – Konten für Schulen (Bereich Schulen)

Lehrkräfte und Schulleitungen bekommen ein eigenes Konto mit der neuen Systemrolle **Schule** (`school`, Berechtigung `school.area`). Jedes Schulkonto gehört zu **genau einer Schule** und sieht nur deren Daten.

## Konto anlegen

Koordination, Geschäftsstelle und Vorstand (`partners.manage`) laden eine **Ansprechperson mit E-Mail-Adresse** auf der Seite der Schule ein („Konto einladen“ in der Tabelle der Ansprechpersonen). Das Konto
- entsteht im Status „eingeladen“ und bekommt die Einladung per E-Mail (Link sieben Tage gültig, wie bei allen Konten),
- hat die Rolle Schule und **keine Person mit Stammdaten** (nur den Namen aus der Ansprechperson),
- ist über die Tabelle `partner_users` mit der Schule (und der Ansprechperson) verknüpft.

Abgelehnt wird, wenn die Ansprechperson keine gültige Adresse hat, schon ein Konto hat, die Adresse schon von einem anderen Konto benutzt wird oder die Ansprechperson zu einer anderen Schule gehört. „Einladung erneut senden“ geht nur, solange sich die Person noch nicht angemeldet hat. Sperren und Rollen verwaltet die Nutzerverwaltung wie bei allen Konten. **Schulen mit Konten lassen sich nicht löschen** (stattdessen Konten sperren und die Schule auf „Pausiert“ oder „Vergangen“ setzen).

## Was Schulen tun können (`/schulen`)

| Seite | Inhalt |
|---|---|
| Übersicht | die Seminare der eigenen Schule (ohne abgesagte) mit Stand, Terminen und Kostenvoranschlag (Druckansicht der aktuellen Fassung); **Termin und Kostenvoranschlag bestätigen** (nur im Status „Terminiert“ mit Kostenvoranschlag, einmal je Seminar); **Rückmeldung** an die Koordination |
| Veranstaltungen | Anmeldelisten für Großveranstaltungen hochladen und die eigenen Anmeldungen sehen und entfernen (`docs/53_GROSSVERANSTALTUNGEN.md`) |
| Seminar anfragen | Leistung, Wunschzeitraum, Teilnehmende, Klassenstufe, Hinweise; legt ein Seminar im Status „Anfrage“ an |

Die **Koordination** bekommt bei Anfrage, Bestätigung und Rückmeldung eine E-Mail ohne den Inhalt der Nachricht (der steht im Verlauf des Seminars: „Schule hat bestätigt“, „Rückmeldung der Schule“). Die Bestätigung der Schule ersetzt nicht die der Koordination: Das Seminar auf „Bestätigt“ setzt weiterhin die Koordination.

Im Konto und in der Übersicht des Bereichs Schulen zeigt „Ihre Aufgaben“ Schulen, wie viele Seminare auf ihre Bestätigung warten.

## Grenzen

Schulkonten sehen keine anderen Schulen, keine Teamenden, keine Preise und keine früheren Fassungen von Kostenvoranschlägen. Die Abrufe fremder Seminare oder Kostenvoranschläge antworten mit „nicht gefunden“. Das allgemeine Protokoll enthält nur Konto, Rolle und Schule, keine Nachrichten.

## Einführung

Die Migration `2026_11_17_000001_create_partner_users_table` legt die Rolle „Schule“ selbst an; ein erneutes Seeden ist nicht nötig.

## Sperren und Erinnerung

- **Konto sperren oder entsperren:** Auf der Seite der Schule gibt es bei jedem Konto „Sperren“ bzw. „Entsperren“ (z. B. wenn eine Lehrkraft die Schule verlässt). Das Sperren beendet alle Sitzungen sofort. Nicht angemeldete (eingeladene) Konten lassen sich nicht sperren, dort gibt es nur „Einladung erneut senden“.
- **Erinnerung vor dem Anmeldeschluss:** `php artisan events:remind-schools` (täglich geplant, `--dry-run` zählt nur) schickt aktiven Schulkonten **einmal** eine Mail, wenn der Anmeldeschluss einer offenen Veranstaltung innerhalb von `portal.events.school_reminder_days` (Standard 3) Tagen liegt. Die Mail nennt, wie viele Schülerinnen und Schüler der Schule bisher angemeldet sind. Veranstaltungen ohne Anmeldeschluss lösen keine Erinnerung aus.
